<?php

namespace everyday\IncrementalStaticRegeneration\services;

use Craft;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\events\ModelEvent;
use everyday\IncrementalStaticRegeneration\events\RegisterTriggersEvent;
use everyday\IncrementalStaticRegeneration\helpers\TriggerHelper;
use everyday\IncrementalStaticRegeneration\jobs\MakeRequest;
use everyday\IncrementalStaticRegeneration\models\Settings;
use yii\base\Component;
use yii\base\Event;
use yii\queue\Queue;

class Triggers extends Component
{
    public const EVENT_REGISTER_TRIGGERS = "registerTriggers";

    public array $triggers = [];

    private Queue $queue;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->queue = Craft::$app->getQueue();
    }

    private function getDefaultTriggers(Settings $settings): array
    {
        return [
            [
                'class'   => Entry::class,
                'event'   => Entry::EVENT_AFTER_SAVE,
                'handler' => function (ModelEvent $event) use ($settings) {
                    return TriggerHelper::entryTrigger($event, $settings);
                },
            ],
            [
                'class'   => Entry::class,
                'event'   => Entry::EVENT_AFTER_DELETE,
                'handler' => function (Event $event) use ($settings) {
                    return TriggerHelper::entryTrigger($event, $settings);
                },
            ],
            [
                'class'   => Asset::class,
                'event'   => Asset::EVENT_AFTER_SAVE,
                'handler' => function (ModelEvent $event) use ($settings) {
                    return TriggerHelper::assetAfterSaveTrigger($event, $settings);
                },
            ],
            [
                'class'   => GlobalSet::class,
                'event'   => GlobalSet::EVENT_AFTER_SAVE,
                'handler' => function (ModelEvent $event) use ($settings) {
                    return TriggerHelper::globalSetAfterSaveTrigger($event, $settings);
                },
            ],
        ];
    }

    public function initialize(Settings $settings): void
    {
        $additionalTriggers = $settings->additionalTriggers ?? [];
        $this->triggers     = [...$this->getDefaultTriggers($settings), ...$additionalTriggers];

        if ($this->hasEventHandlers(self::EVENT_REGISTER_TRIGGERS)) {
            $event = new RegisterTriggersEvent([
                'triggers' => $this->triggers,
            ]);
            $this->trigger(self::EVENT_REGISTER_TRIGGERS, $event);
            $this->triggers = $event->triggers;
        }

        $this->registerTriggers();
    }

    private function registerTriggers(): void
    {
        // Intelligently merge triggers where 'class' and 'event' are the same in order to bulk run the handlers and deduplicate values
        $deduplicatedTriggers = [];
        foreach ($this->triggers as $row) {
            if (isset($deduplicatedTriggers[$row['class']][$row['event']])) {
                $deduplicatedTriggers[$row['class']][$row['event']] = [
                    ...$deduplicatedTriggers[$row['class']][$row['event']], $row['handler'],
                ];

                continue;
            }

            $deduplicatedTriggers[$row['class']][$row['event']] = [$row['handler']];
        }

        foreach ($deduplicatedTriggers as $class => $triggers) {
            foreach ($triggers as $event => $handlers) {
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
}