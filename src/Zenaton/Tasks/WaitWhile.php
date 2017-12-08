<?php

namespace Zenaton\Tasks;

use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Interfaces\EventInterface;
use Zenaton\Interfaces\WaitWhileInterface;
use Zenaton\Traits\IsImplementationOfTrait;
use Zenaton\Traits\WithDuration;
use Zenaton\Traits\Zenatonable;

class WaitWhile implements WaitWhileInterface
{
    use Zenatonable;
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
