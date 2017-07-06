<?php

namespace Zenaton\Common\Services;

use Zenaton\Common\Traits\SingletonTrait;

class Metrics
{
    use SingletonTrait;

    protected $networkDuration = 0;

    public function addNetworkDuration($duration)
    {
        $this->networkDuration += $duration;
    }

    public function getNetworkDuration()
    {
        return $this->networkDuration;
    }
}
