<?php

namespace everyday\IncrementalStaticRegeneration\helpers;

use Craft;
use craft\elements\Entry;
use GuzzleHttp\Client;

class WebRequest
{
    private static function webClient(): Client
    {
        $customConfig = Craft::$app->config->getConfigFromFile('incremental-static-regeneration');

        return Craft::createGuzzleClient([
            'base_uri' => $customConfig["isrEndpoint"],
            'headers'  => [
                'Authorization' => $customConfig["isrSecret"],
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

            self::webClient()->post("?uri=/$uri&site=$siteHandle");
        }
    }
}