# Craft Incremental Static Regeneration

This plugin is best used with a front-end framework that supports [Incremental Static Regeneration](https://nextjs.org/docs/basic-features/data-fetching/incremental-static-regeneration), like Next.js.

The whole purpose of this plugin is to hit an API in your front-end, with the URI and siteHandle of the entry being updated, in order to trigger a rebuild of the front-end page.

## Features

- Regenerate all pages when a global is updated.
- Regenerate all entries that use a specific asset when an asset is updated.
- Exclude specific globals or sections from sending a request to your front-end's API.
- Allows adding custom events to accommodate any other use cases you may have.

## Custom events

Create a config file in your config folder and call it `incremental-static-regeneration.php`. You can see the available settings [here](https://github.com/everyday-as/craft-incremental-static-regeneration/blob/main/src/config.php).

As an example, let's add support for [Retour](https://plugins.craftcms.com/retour):

```php
<?php

use nystudio107\retour\events\RedirectEvent;
use nystudio107\retour\services\Redirects;

return [

    'additionalEvents' => [
        [
            'class'   => Redirects::class,
            'event'   => Redirects::EVENT_AFTER_SAVE_REDIRECT,
            'handler' => static function (RedirectEvent $event) {
                return [$event->legacyUrl];
            },
        ],
        [
            'class'   => Redirects::class,
            'event'   => Redirects::EVENT_AFTER_DELETE_REDIRECT,
            'handler' => static function (RedirectEvent $event) {
                return [$event->legacyUrl];
            },
        ]
    ],

];
```

Yeah... it's that easy. Now every time you edit a redirect in Retour it will trigger an ISR update for the entry on the given legacy URL.

## Local development

### Installing the Composer dependencies:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/opt \
    -w /opt \
    craftcms/cli:8.1-dev \
    composer install --ignore-platform-reqs
```