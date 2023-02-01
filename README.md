# Craft Incremental Static Regeneration

## Local development

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/opt \
    -w /opt \
    craftcms/cli:8.1-dev \
    composer install --ignore-platform-reqs
```