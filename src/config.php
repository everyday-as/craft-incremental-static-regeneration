<?php

return [

    // The public-facing name of the plugin
    'pluginName' => "Incremental Static Regeneration",

    // The base url for the application we'll be hitting
    'endpoint' => null,

    // The secret key we will send along the request in the `Authorization` header
    'secret' => null,

    // Additional events we should trigger updates for. Return an associative array with the event as a key and a function taking the event's signature.
    // Return an array of URIs to trigger updates for.
    /* Syntax:
        [
            \craft\elements\Entry::EVENT_AFTER_SAVE => function (Entry $entry) {
                // Return URIs we should trigger updates for
                return [$entry->uri];
            },
        ]
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