<?php

namespace everyday\IncrementalStaticRegeneration\models;

use Craft;
use craft\base\Model;
use Everyday\IncrementalStaticRegeneration\IncrementalStaticRegeneration;

class Settings extends Model
{
    public string $isrEndpoint = 'http://localhost:3000/api/revalidate';
    public string $isrSecret = 'secret';
    public bool $enablePreviews = true;
    public string $previewEndpoint = 'http://localhost:3000/api/preview';
    public string $previewSecret = 'secret';
    public bool $removeDefaultPreviewTarget = false;
    public array $additionalTriggers = [];
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
            // ISR endpoint
            ['isrEndpoint', 'required', 'message' => Craft::t(IncrementalStaticRegeneration::PLUGIN_HANDLE, 'ISR endpoint URL is required')],
            ['isrEndpoint', 'url', 'defaultScheme' => 'http', 'pattern' => '/^{schemes}:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.?[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])/i'],
            ['isrEndpoint', 'default', 'value' => 'http://localhost:3000/api/revalidate'],
            // ISR secret
            ['isrSecret', 'string'],
            // Preview endpoint
            ['previewEndpoint', 'required', 'message' => Craft::t(IncrementalStaticRegeneration::PLUGIN_HANDLE, 'Preview endpoint URL is required')],
            ['previewEndpoint', 'url', 'defaultScheme' => 'http', 'pattern' => '/^{schemes}:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.?[A-Z0-9][A-Z0-9_-]*)+)(?::\d{1,5})?(?:$|[?\/#])/i'],
            ['previewEndpoint', 'default', 'value' => 'http://localhost:3000/api/preview'],
            // Preview secret
            ['previewSecret', 'string'],
        ];
    }
}