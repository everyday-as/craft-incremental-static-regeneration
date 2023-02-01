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
            'base_uri' => $customConfig["endpoint"],
            'headers'  => [
                'Authorization' => $customConfig["secret"],
            ],
        ]);
    }

    public static function makeRequest(string $uri): void
    {
        // Find the entries associated with this $uri
        $entries = Entry::find()->site('*')->uri('aktuelt/smeller-snart-inn-i-asteroide')->all();

        foreach ($entries as $entry) {
            if (!$entry) {
                return;
            }

            $siteHandle = $entry->site->handle;

            self::webClient()->post("?uri=/$uri&site=$siteHandle");
        }
    }
}