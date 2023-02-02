# Craft Incremental Static Regeneration

This plugin is best used with a front-end framework that supports [Incremental Static Regeneration](https://nextjs.org/docs/basic-features/data-fetching/incremental-static-regeneration), like Next.js.

The whole purpose of this plugin is to hit an API in your front-end, with the URI and siteHandle of the entry being updated, in order to trigger a rebuild of the front-end page.

## Installation

```
composer require everyday/craft-incremental-static-regeneration
```

## Features

- Regenerate all pages when a global is updated.
- Regenerate all entries that use a specific asset when an asset is updated.
- Bypass ISR in preview mode.
- Exclude specific globals or sections from sending a request to your front-end's API.
- Allows adding custom events to accommodate any other use cases you may have.

## Usage

After configuring the endpoint and a secret key you need to prepare your front-end API to consume the following query parameters in a POST request:

```
uri
site
```

URI is the path to the entry in Craft. Site is the site handle, which you may or may not need to use depending on your front-end's i18n implementation (or lack thereof).

An example of a revalidate API endpoint (`pages/api/revalidate.ts`) for ISR in Next.js for use with this plugin is:

```ts
import type { NextApiRequest, NextApiResponse } from 'next'
import { getLocaleByCraftSite } from "../../helpers/utils";

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(404)
  }

  if (req.headers.authorization !== process.env.INCREMENTAL_GENERATION_SECRET) {
    return res.status(401).json({message: 'Invalid token'})
  }

  const uri = req.query.uri as string
  const site = req.query.site as string

  if (!uri) {
    return res.status(422).json({message: 'No uri supplied'})
  }

  if (!site) {
    return res.status(422).json({message: 'No site supplied'})
  }

  if (uri === '/') return

  const locale = getLocaleByCraftSite(site)

  try {
    await res.revalidate(`/${locale}${uri}`)

    return res.json({ revalidated: true })
  } catch (err) {
    return res.status(500).send('Error revalidating')
  }
}
```

This code obviously won't work right out of the box for you, for example you may need to remove the use of `getLocaleByCraftSite`, or implement a similar function yourself.

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