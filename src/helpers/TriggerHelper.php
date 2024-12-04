<?php

namespace everyday\IncrementalStaticRegeneration\helpers;

use craft\base\Element;
use craft\elements\Entry;
use craft\events\ModelEvent;
use craft\helpers\ElementHelper;
use everyday\IncrementalStaticRegeneration\models\Settings;
use yii\base\Event;
use yii\base\InvalidConfigException;

class TriggerHelper
{
    private static function getRelatedUris(Element $element, Settings $settings): array
    {
        $entries = array_filter(Entry::find()
                                     ->site('*')
                                     ->uri(':notempty:')
                                     ->relatedTo($element)
                                     ->all(), fn($entry) => !self::entryEntryTypeDisabled($entry, $settings));

        return array_map(static fn($entry) => $entry->uri, $entries);
    }

    private static function entryEntryTypeDisabled(Entry $entry, Settings $settings): bool
    {
        $section = $entry->getSection();

        if(!$section) {
            return true;
        }

        foreach ($section->getEntryTypes() as $entryType) {
            if (in_array($entryType->handle, $settings->excludedSections[$section->handle] ?? [],
                true)) {
                return true;
            }
        }

        return false;
    }

    private static function getAllUris(Settings $settings): array
    {
        $entries = array_filter(Entry::find()
                                     ->uri(':notempty:')
                                     ->all(), fn($entry) => !self::entryEntryTypeDisabled($entry, $settings));

        return array_map(static fn($entry) => $entry->uri, $entries);
    }

    public static function entryTrigger(ModelEvent|Event $event, Settings $settings): ?array
    {
        if (self::entryEntryTypeDisabled($event->sender, $settings)) {
            return null;
        }

        if (ElementHelper::isDraft($event->sender)
            || !$event->sender->getEnabledForSite()
            || ElementHelper::isRevision($event->sender)) {
            return null;
        }

        return [
            ...self::getRelatedUris($event->sender, $settings),
            $event->sender->uri,
        ];
    }

    public static function assetAfterSaveTrigger(ModelEvent $event, Settings $settings): ?array
    {
        if (!$settings->enableAssets
            || !$event->sender->propagating
            || !$event->sender->enabled
            || !$event->sender->getEnabledForSite()) {
            return null;
        }

        return self::getRelatedUris($event->sender, $settings);
    }

    public static function globalSetAfterSaveTrigger(ModelEvent $event, Settings $settings): ?array
    {
        if (!$settings->enableGlobalSets
            || !$event->sender->enabled
            || !$event->sender->getEnabledForSite()
            || in_array($event->sender->handle, $settings->excludedGlobalSets ?? [], true)) {
            return null;
        }

        return self::getAllUris($settings);
    }
}
