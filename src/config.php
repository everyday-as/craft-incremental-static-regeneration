<?php

return [

    // The public-facing name of the plugin
    'pluginName' => "Incremental Static Regeneration",

    // The base url for the application we'll be hitting
    'endpoint' => null,

    // The secret key we will send along the request in the `Authorization` header
    'secret' => null,

    // Additional events we should trigger updates for. Return an associative array with the event as a key and a function taking the event's signature.
    // Return an array of [uri, siteHandle] tuples to trigger updates for.
    /* If you wanted to add support for Retour's AFTER_SAVE_REDIRECT event you'd do the following:
        [
            'class'   => Redirects::class,
            'event'   => Redirects::EVENT_AFTER_SAVE_REDIRECT,
            'handler' => static function (RedirectEvent $event) {
                $sites = $event->siteId ? [Site::find()->where(['id' => $event->siteId])->one()] : Site::find()->all();
                return array_map(static fn($site) => [$event->legacyUrl, $site->handle], $sites);
            },
        ],
    */
    'additionalEvents' => [],

    // Enable re-validation of related entries when assets are updated
    'enableAssets' => true,

    // Enable re-validation of all entries when globalSets are updated
    'enableGlobalSets' => true,

    // GlobalSets to exclude from ISR when updated
    /* Syntax:
     [
        "myGlobalHandle"
     ]
     */
    'excludedGlobalSets' => [],

    // Sections to exclude from ISR when updated
    /* Syntax:
        [
            "mySectionHandle" => ["default", "myOtherEntryTypeHandle"]
        ]
     */
    'excludedSections' => [],

];