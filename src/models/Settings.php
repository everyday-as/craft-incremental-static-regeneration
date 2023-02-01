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
     * @var array An associative array defining additional fields to be indexed along with the defaults one.
     * Each additional field should be declared as the name of the attribute as the key and an associative array for the value
     * in which the keys can be:
     * - `mapping`: an array providing the elasticsearch mapping definition for the field. For example:
     *   ```php
     *   [
     *        'type'  => 'text',
     *        'store' => true
     *   ]
     *   ```
     * - `highlighter` : an object defining the elasticsearch highlighter behavior for the field. For example: `(object)[]`
     * - `value` : either a string or a callable function taking one argument of \craft\base\Element type and returning the value of the field, for example:
     *   ```php
     *   function (\craft\base\Element $element) {
     *       return ArrayHelper::getValue($element, 'color.hex');
     *   }
     *   ```
     */
    public array $extraFields = [];

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