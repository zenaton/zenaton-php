<?php

namespace Zenaton\Tasks;

use Zenaton\Common\Exceptions\ExternalZenatonException;
use Zenaton\Common\Interfaces\EventInterface;
use Zenaton\Common\Interfaces\WaitWhileInterface;
use Zenaton\Common\Traits\IsImplementationOfTrait;
use Zenaton\Common\Traits\WithDuration;

class WaitWhile implements WaitWhileInterface
{
    use IsImplementationOfTrait;
    use WithDuration;

    protected $event;

    public function __construct($event)
    {
        if ( ! is_string($event) || ! $this->isImplementationOf($event, EventInterface::class)) {
            throw new ExternalZenatonException(self::class.': Invalid parameter - argument must a class name implementing '.EventInterface::class);
        }

        $this->event = $event;
    }

    public function handle()
    {
        // time_sleep_until($this->getTimestamp());
    }

    public function getEvent()
    {
        return $this->event;
    }
}
