<?php

namespace everyday\IncrementalStaticRegeneration\models;

use Craft;
use craft\base\Model;
use Everyday\IncrementalStaticRegeneration\IncrementalStaticRegeneration;

class Settings extends Model
{
    public string $endpoint = 'http://127.0.0.1:3000/api/revalidate';
    public string $secret = 'secret';
    public array $additionalEvents = [];
    public bool $enableGlobalSets = true;
    public bool $enableAssets = true;
    public array $excludedGlobalSets = [];
    public array $excludedSections = [];

    /**
     * Returns the validation rules for attributes.
     * @return array
     */
    public function rules(): array
    {
        return [
            ['endpoint', 'required', 'message' => Craft::t(IncrementalStaticRegeneration::PLUGIN_HANDLE, 'Endpoint URL is required')],
            ['endpoint', 'url', 'defaultScheme' => 'http', 'pattern' => '/^{schemes}:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.?[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])/i'],
            ['endpoint', 'default', 'value' => 'http://localhost:3000/api/revalidate'],
            ['secret', 'string'],
        ];
    }
}