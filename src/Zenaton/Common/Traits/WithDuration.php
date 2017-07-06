<?php

namespace Zenaton\Common\Traits;

use Carbon\Carbon;

trait WithDuration
{
    protected $timeoutBuffer = [];
    protected $timeoutNow;
    protected $timeoutCarbon;

    public function __call($name, $arguments)
    {
        $method = '_'.$name;
        if (method_exists($this, $method)) {
            // then use it
            $this->timeoutBuffer[] = [$method, $arguments];
        } else {
            // else thrown expected error
            return call_user_func([$this->{$name}, $arguments]);
        }

        return $this;
    }

    public function getTimeoutTimestamp()
    {
        // do timezone first
        foreach ($this->timeoutBuffer as $call) {
            $method = $call[0];
            if ($method === '_timezone') {
                isset($call[1][0]) ? $this->{$method}($call[1][0]) : $this->{$method}();
            }
        }

        // then other instructions
        foreach ($this->timeoutBuffer as $call) {
            $method = $call[0];
            if ($method !== '_timezone') {
                isset($call[1][0]) ? $this->{$method}($call[1][0]) : $this->{$method}();
            }
        }

        // clean timeoutBuffer, just in case someone uses it again
        $this->timeoutBuffer = [];

        return $this->timeoutCarbon ? $this->timeoutCarbon->timestamp : PHP_INT_MAX;
    }

    protected function _seconds($s)
    {
        $this->getTimeoutCarbon()->addSeconds($s);

        return $this;
    }

    protected function _minutes($m)
    {
        $this->getTimeoutCarbon()->addMinutes($m);

        return $this;
    }

    protected function _hours($h)
    {
        $this->getTimeoutCarbon()->addHours($h);

        return $this;
    }

    protected function _days($d)
    {
        $this->getTimeoutCarbon()->addDays($d);

        return $this;
    }

    protected function _weeks($w)
    {
        $this->getTimeoutCarbon()->addWeeks($w);

        return $this;
    }

    protected function _months($m)
    {
        $this->getTimeoutCarbon()->addMonths($m);

        return $this;
    }

    protected function _years($y)
    {
        $this->getTimeoutCarbon()->addYears($y);

        return $this;
    }

    protected function getTimeoutCarbon()
    {
        if ( ! $this->timeoutCarbon) {
            $this->timeoutNow = Carbon::now();
            $this->timeoutCarbon = $this->timeoutNow->copy();
        }

        return $this->timeoutCarbon;
    }
}
