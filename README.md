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

Check out the [wiki](https://github.com/everyday-as/incremental-static-regeneration-for-craft/wiki/) :)

## Local development

<details>
  <summary>You probably don't need this, so we've collapsed this section.</summary>

### Installing the Composer dependencies:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/opt \
    -w /opt \
    craftcms/cli:8.1-dev \
    composer install --ignore-platform-reqs
```
</details>
