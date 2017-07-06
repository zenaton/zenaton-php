<?php

namespace Zenaton\Worker\Tasks;

use Zenaton\Common\Exceptions\ExternalZenatonException;
use Zenaton\Common\Interfaces\EventInterface;
use Zenaton\Common\Interfaces\WaitInterface;
use Zenaton\Common\Traits\IsImplementationOfTrait;
use Zenaton\Common\Traits\WithTimeout;

class Wait implements WaitInterface
{
    use IsImplementationOfTrait;
    use WithTimeout;

    protected $event;

    public function __construct($event = null)
    {
        if ( ! is_null($event) && ( ! is_string($event) || ! $this->isImplementationOf($event, EventInterface::class))) {
            throw new ExternalZenatonException(self::class.': Invalid parameter - argument must a class name implementing '.EventInterface::class);
        }

        $this->event = $event;
    }

    public function handle()
    {
        time_sleep_until($this->getTimeoutTimestamp());
    }

    public function getEvent()
    {
        return $this->event;
    }
}
