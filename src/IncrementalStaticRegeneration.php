<?php

namespace everyday\IncrementalStaticRegeneration;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Entry;
use craft\events\RegisterPreviewTargetsEvent;
use craft\helpers\UrlHelper;
use everyday\IncrementalStaticRegeneration\models\Settings;
use everyday\IncrementalStaticRegeneration\services\Triggers;
use yii\base\Event;

class IncrementalStaticRegeneration extends Plugin
{
    public const PLUGIN_HANDLE = "incremental-static-regeneration";

    public bool $hasCpSettings = true;

    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'triggers' => Triggers::class
        ]);

        $this->triggers->initialize($this->getSettings());
        $this->setupPreviewTargets();
    }

    private function setupPreviewTargets(): void
    {
        $settings = $this->getSettings();

        Event::on(
            Entry::class,
            Element::EVENT_REGISTER_PREVIEW_TARGETS,
            function (RegisterPreviewTargetsEvent $event) use ($settings) {
                if (!$settings->enablePreviews) {
                    return;
                }

                $element = $event->sender;
                if ($element->uri !== null) {
                    // Remove the default preview target, if any. We want to be the default
                    if ($settings->removeDefaultPreviewTarget && isset($event->previewTargets[0]['label']) && $event->previewTargets[0]['label'] === Craft::t('app',
                            'Primary {type} page', [
                                'type' => 'entry',
                            ])) {
                        array_shift($event->previewTargets);
                    }

                    // We want to be the default preview target, if possible
                    array_unshift($event->previewTargets, [
                        'label'   => Craft::t($this->handle, 'Live preview'),
                        'url'     => UrlHelper::siteUrl($settings->previewEndpoint, [
                            'uri'    => "/$element->uri",
                            'site'   => $element->site->handle,
                            'secret' => $settings->previewSecret,
                        ]),
                        'refresh' => "1",
                    ]);
                }
            });
    }

    public static function config(): array
    {
        return [
            'components' => [
                'triggers' => ['class' => Triggers::class],
            ],
        ];
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