<?php

namespace everyday\IncrementalStaticRegeneration\jobs;

use Craft;
use craft\queue\BaseJob;
use everyday\IncrementalStaticRegeneration\helpers\WebRequest;

class MakeRequest extends BaseJob
{
    public string $uri;

    public function execute($queue): void
    {
        WebRequest::makeRequest($this->uri);
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Triggering ISR for "{uri}"', [
            'uri' => $this->uri,
        ]);
    }
}