<?php

return [

    // The public-facing name of the plugin
    'pluginName' => "Incremental Static Regeneration",

    // The URL we'll hit when we're triggering ISR (https://nextjs.org/docs/basic-features/data-fetching/incremental-static-regeneration)
    'isrEndpoint' => 'http://localhost:3000/api/revalidate',

    // The secret key we will send along the request in the `Authorization` header
    'isrSecret' => null,

    // Enable previews that bypass ISR. This requires some setting up in your front-end (https://nextjs.org/docs/advanced-features/preview-mode)
    'enablePreviews' => true,

    // Remove the default preview target from the preview target list
    'removeDefaultPreviewTarget' => false,

    // The URL we'll redirect the user from when they preview the site (https://nextjs.org/docs/advanced-features/preview-mode)
    'previewEndpoint' => 'http://localhost:3000/api/preview',

    // The secret key we will send along the redirect request in the `secret` query parameter
    'previewSecret' => null,

    // Additional events we should trigger updates for. Return an array of uris to trigger updates for.
    /* If you wanted to add support for Retour's AFTER_SAVE_REDIRECT event you'd do the following:
        [
            'class'   => Redirects::class,
            'event'   => Redirects::EVENT_AFTER_SAVE_REDIRECT,
            'handler' => static function (RedirectEvent $event) {
                return [$event->legacyUrl];
            },
        ],
    */
    'additionalTriggers' => [],

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