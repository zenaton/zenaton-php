<?php

namespace Zenaton\v2\Tasks;

use Zenaton\Exceptions\ExternalZenatonException;

use Zenaton\v2\Interfaces\EventInterface;
use Zenaton\v2\Interfaces\WaitInterface;
use Zenaton\v2\Traits\IsImplementationOfTrait;
use Zenaton\v2\Traits\WithTimestamp;
use Zenaton\v2\Traits\Zenatonable;

class Wait implements WaitInterface
{
    use Zenatonable;
    use IsImplementationOfTrait;
    use WithTimestamp;

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
