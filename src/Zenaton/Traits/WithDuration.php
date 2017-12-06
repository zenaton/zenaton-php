<?php

namespace Zenaton\Traits;

use Carbon\Carbon;

trait WithDuration
{
    protected $_carbonBuffer = [];
    protected $_carbonNow;
    protected $_carbonThen;

    /*
     *  Return duration in seconds
     */
    public function getDuration()
    {
        // apply methods in buffer (timezone first)
        $this->_applyBuffer();

        return $this->_carbonNow ? $this->_carbonNow->diffInSeconds($this->_carbonThen) : PHP_INT_MAX;
    }

    /*
     *  Magical method to bufferize methods before applying them
     *  in order to be able to apply timezone first
     */
    public function __call($name, $arguments)
    {
        $method = '_'.$name;
        if (method_exists($this, $method)) {
            // if exists use it
            $this->_carbonBuffer[] = [$method, $arguments];
        } else {
            // else thrown expected error
            return call_user_func([$this->{$name}, $arguments]);
        }

        return $this;
    }

    protected function _applyBuffer()
    {
        // do timezone first
        foreach ($this->_carbonBuffer as $call) {
            $method = $call[0];
            if ($method === '_timezone') {
                isset($call[1][0]) ? $this->{$method}($call[1][0]) : $this->{$method}();
            }
        }

        // then other instructions
        foreach ($this->_carbonBuffer as $call) {
            $method = $call[0];
            if ($method !== '_timezone') {
                isset($call[1][0]) ? $this->{$method}($call[1][0]) : $this->{$method}();
            }
        }

        // empty buffer
        $this->_carbonBuffer = [];
    }

    protected function _seconds($s = 1)
    {
        $this->getCarbonThen()->addSeconds($s);

        return $this;
    }

    protected function _minutes($m = 1)
    {
        $this->getCarbonThen()->addMinutes($m);

        return $this;
    }

    protected function _hours($h = 1)
    {
        $this->getCarbonThen()->addHours($h);

        return $this;
    }

    protected function _days($d = 1)
    {
        $this->getCarbonThen()->addDays($d);

        return $this;
    }

    protected function _weeks($w = 1)
    {
        $this->getCarbonThen()->addWeeks($w);

        return $this;
    }

    protected function _months($m = 1)
    {
        $this->getCarbonThen()->addMonths($m);

        return $this;
    }

    protected function _years($y = 1)
    {
        $this->getCarbonThen()->addYears($y);

        return $this;
    }

    protected function getCarbonThen()
    {
        if ( ! $this->_carbonThen) {
            $this->_carbonBuffer = [];
            $this->_carbonNow = Carbon::now();
            $this->_carbonThen = $this->_carbonNow->copy();
        }

        return $this->_carbonThen;
    }
}
