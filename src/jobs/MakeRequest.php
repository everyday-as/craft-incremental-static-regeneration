<?php

namespace everyday\IncrementalStaticRegeneration\jobs;

use Craft;
use craft\queue\BaseJob;
use everyday\IncrementalStaticRegeneration\helpers\WebRequest;

class MakeRequest extends BaseJob
{
    public string $uri;

    public string $siteHandle;

    public function execute($queue): void
    {
        WebRequest::makeRequest($this->uri, $this->siteHandle);
    }

    protected function defaultDescription(): ?string
    {
        return Craft::t('app', 'Performing ISR for "{uri}" on site "{siteHandle}"', [
            'uri' => $this->uri,
            'siteHandle' => $this->siteHandle
        ]);
    }
}