<?php

namespace everyday\IncrementalStaticRegeneration\helpers;

use Craft;
use craft\elements\Entry;
use everyday\IncrementalStaticRegeneration\IncrementalStaticRegeneration;
use GuzzleHttp\Client;

class WebRequest
{
    private static function webClient(): Client
    {
        $plugin   = IncrementalStaticRegeneration::getInstance();
        $settings = $plugin->getSettings();

        return Craft::createGuzzleClient([
            'base_uri' => $settings->isrEndpoint,
            'headers'  => [
                'Authorization' => $settings->isrSecret,
            ],
        ]);
    }

    public static function makeRequest(string $uri): void
    {
        // Find the entries associated with this $uri
        $entries = Entry::find()->site('*')->uri($uri)->all();

        foreach ($entries as $entry) {
            if (!$entry) {
                return;
            }

            $siteHandle = $entry->site->handle;

            self::webClient()->post("", [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => json_encode([
                    'uri'  => $uri,
                    'site' => $siteHandle,
                ]),
            ]);
        }
    }
}