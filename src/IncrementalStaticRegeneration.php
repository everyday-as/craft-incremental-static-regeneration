<?php

namespace everyday\IncrementalStaticRegeneration;

use Craft;
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use everyday\IncrementalStaticRegeneration\jobs\MakeRequest;
use everyday\IncrementalStaticRegeneration\models\Settings;
use yii\base\Event;
use yii\queue\Queue;

class IncrementalStaticRegeneration extends \craft\base\Plugin
{
    public const PLUGIN_HANDLE = "incremental-static-regeneration";

    private Queue $queue;

    public bool $hasCpSettings = true;

    public function __construct($id, $parent = null, array $config = [])
    {
        parent::__construct($id, $parent, $config);

        $this->queue = Craft::$app->getQueue();
    }

    private function getRelatedUris(Element $element): array
    {
        $relatedEntries = Entry::find()->relatedTo($element)->all();

        return array_map(static fn($entry) => $entry->uri, $relatedEntries);
    }

    private function getAllUris(): array
    {
        $entries = Entry::find()->uri(':notempty:')->all();

        return array_map(static fn($entry) => $entry->uri, $entries);
    }

    private function entryEvent(ModelEvent|Event $event, ?Settings $settings): ?array
    {
        foreach ($event->sender->section->entryTypes as $entryType) {
            if (in_array($entryType->handle, $settings->excludedSections[$event->sender->section->handle] ?? [],
                true)) {
                return null;
            }
        }

        if (!$event->sender->enabled
            || ElementHelper::isDraft($event->sender)
            || !$event->sender->getEnabledForSite()
            || ElementHelper::isRevision($event->sender)) {
            return null;
        }

        return [
            ...$this->getRelatedUris($event->sender),
            $event->sender->uri,
        ];
    }

    private function assetAfterSaveEvent(ModelEvent $event, ?Settings $settings): ?array
    {
        if (!$settings?->enableAssets
            || !$event->sender->enabled
            || !$event->sender->getEnabledForSite()) {
            return null;
        }

        return $this->getRelatedUris($event->sender);
    }

    private function globalSetAfterSaveEvent(ModelEvent $event, ?Settings $settings): ?array
    {
        if (!$settings?->enableGlobalSets
            || !$event->sender->enabled
            || !$event->sender->getEnabledForSite()
            || in_array($event->sender->handle, $settings?->excludedGlobalSets ?? [], true)) {
            return null;
        }

        return $this->getAllUris();
    }

    public function init(): void
    {
        parent::init();

        $settings = $this->getSettings();

        $defaultEvents = [
            [
                'class'   => Entry::class,
                'event'   => Entry::EVENT_AFTER_SAVE,
                'handler' => function (ModelEvent $event) use ($settings) {
                    return $this->entryEvent($event, $settings);
                },
            ],
            [
                'class'   => Entry::class,
                'event'   => Entry::EVENT_AFTER_DELETE,
                'handler' => function (Event $event) use ($settings) {
                    return $this->entryEvent($event, $settings);
                },
            ],
            [
                'class'   => Asset::class,
                'event'   => Asset::EVENT_AFTER_SAVE,
                'handler' => function (ModelEvent $event) use ($settings) {
                    return $this->assetAfterSaveEvent($event, $settings);
                },
            ],
            [
                'class'   => GlobalSet::class,
                'event'   => GlobalSet::EVENT_AFTER_SAVE,
                'handler' => function (ModelEvent $event) use ($settings) {
                    return $this->globalSetAfterSaveEvent($event, $settings);
                },
            ],
        ];

        $additionalEvents = $settings->additionalEvents ?? [];
        $allEvents        = [...$defaultEvents, ...$additionalEvents];

        // Intelligently merge events where 'class' and 'event' are the same in order to bulk run the handlers and deduplicate values
        $deduplicatedEvents = [];
        foreach ($allEvents as $row) {
            if (isset($deduplicatedEvents[$row['class']][$row['event']])) {
                $deduplicatedEvents[$row['class']][$row['event']] = [
                    ...$deduplicatedEvents[$row['class']][$row['event']], $row['handler'],
                ];

                continue;
            }

            $deduplicatedEvents[$row['class']][$row['event']] = [$row['handler']];
        }

        foreach ($deduplicatedEvents as $class => $events) {
            foreach ($events as $event => $handlers) {
                Event::on(
                    $class,
                    $event,
                    function ($eventPayload) use ($handlers) {
                        $result = array_unique(array_merge(...
                            array_map(static fn($handler) => $handler($eventPayload) ?? [], $handlers)));

                        if (!$result) {
                            return;
                        }

                        foreach ($result as $uri) {
                            if (!$uri) {
                                continue;
                            }

                            $this->queue->push(new MakeRequest([
                                'uri' => $uri,
                            ]));
                        }
                    }
                );
            }
        }
    }

    /**
     * Creates and returns the model used to store the plugin’s settings.
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \yii\base\Exception
     */
    protected function settingsHtml(): string
    {
        // Get and pre-validate the settings
        $settings = $this->getSettings();

        // Get the settings that are being defined by the config file
        $overrides = Craft::$app->getConfig()->getConfigFromFile(strtolower($this->handle));

        return Craft::$app->view->renderTemplate(
            'incremental-static-regeneration/cp/settings',
            [
                'settings'  => $settings,
                'overrides' => array_keys($overrides),
            ]
        );
    }
}