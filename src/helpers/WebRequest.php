<?php

namespace everyday\IncrementalStaticRegeneration\helpers;

use Craft;
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

    public static function makeRequest(string $uri, string $siteHandle): void
    {
        self::webClient()->post("?uri=/$uri&site={$siteHandle}");
    }
}