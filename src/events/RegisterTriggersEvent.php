<?php

namespace everyday\IncrementalStaticRegeneration\events;

use yii\base\Event;

class RegisterTriggersEvent extends Event
{
    /**
     * @var array
     *
     * Additional ISR triggers
     */
    public array $triggers;
}