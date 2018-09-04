<?php

namespace Zenaton\Tasks;

use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Interfaces\EventInterface;
use Zenaton\Interfaces\WaitInterface;
use Zenaton\Traits\IsImplementationOfTrait;
use Zenaton\Traits\WithTimestamp;
use Zenaton\Traits\Zenatonable;

class Wait implements WaitInterface
{
    use Zenatonable;
    use IsImplementationOfTrait;
    use WithTimestamp;

    protected $event;

    public function __construct($event = null)
    {
        if (!is_null($event) && (!is_string($event) || ! $this->isImplementationOf($event, EventInterface::class))) {
            throw new ExternalZenatonException(self::class.': Invalid parameter - argument must a class name implementing '.EventInterface::class);
        }

        $this->event = $event;
    }

    public function handle()
    {
        // No waiting when executed locally
    }

    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Be a bit less fragile by linearizing only useful data
     */
    public function __sleep()
    {
        return ['event', '_buffer'];
    }
}
