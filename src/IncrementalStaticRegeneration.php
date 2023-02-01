<?php

namespace everyday\IncrementalStaticRegeneration;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use everyday\IncrementalStaticRegeneration\jobs\MakeRequest;
use everyday\IncrementalStaticRegeneration\models\Settings;
use yii\base\Event;
use yii\queue\Queue;

class IncrementalStaticRegeneration extends Plugin
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
        $entries = array_filter(Entry::find()
                                     ->site('*')
                                     ->uri(':notempty:')
                                     ->relatedTo($element)
                                     ->all(), fn($entry) => !$this->entryEntryTypeDisabled($entry));

        return array_map(static fn($entry) => $entry->uri, $entries);
    }

    private function entryEntryTypeDisabled(Entry $entry): bool
    {
        $settings = $this->getSettings();

        foreach ($entry->section->entryTypes as $entryType) {
            if (in_array($entryType->handle, $settings->excludedSections[$entry->section->handle] ?? [],
                true)) {
                return true;
            }
        }

        return false;
    }

    private function getAllUris(): array
    {
        $entries = array_filter(Entry::find()
                                     ->uri(':notempty:')
                                     ->all(), fn($entry) => !$this->entryEntryTypeDisabled($entry));

        return array_map(static fn($entry) => $entry->uri, $entries);
    }

    private function entryEvent(ModelEvent|Event $event): ?array
    {
        if ($this->entryEntryTypeDisabled($event->sender)) {
            return null;
        }

        if (ElementHelper::isDraft($event->sender)
            || !$event->sender->getEnabledForSite()
            || ElementHelper::isRevision($event->sender)) {
            return null;
        }

        return [
            ...$this->getRelatedUris($event->sender),
            $event->sender->uri,
        ];
    }

    private function assetAfterSaveEvent(ModelEvent $event): ?array
    {
        $settings = $this->getSettings();

        if (!$settings?->enableAssets
            || !$event->sender->propagating
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
                'handler' => function (ModelEvent $event) {
                    return $this->entryEvent($event);
                },
            ],
            [
                'class'   => Entry::class,
                'event'   => Entry::EVENT_AFTER_DELETE,
                'handler' => function (Event $event) {
                    return $this->entryEvent($event);
                },
            ],
            [
                'class'   => Asset::class,
                'event'   => Asset::EVENT_AFTER_SAVE,
                'handler' => function (ModelEvent $event) {
                    return $this->assetAfterSaveEvent($event);
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