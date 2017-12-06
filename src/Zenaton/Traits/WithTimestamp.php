<?php

namespace Zenaton\Common\Traits;

use Carbon\Carbon;

trait WithTimestamp
{
    use WithDuration;

    protected $_isTimestamp = false;

    /*
     *  Is the target a timestamp?
     */
    public function isTimestamp()
    {
        return $this->_isTimestamp;
    }

    /*
     *  Return timestamp
     */
    public function getTimestamp()
    {
        // apply methods in buffer (timezone first)
        $this->_applyBuffer();

        return $this->_carbonThen ? $this->_carbonThen->timestamp : PHP_INT_MAX;
    }

    protected function _timestamp($timestamp)
    {
        $this->_isTimestamp = true;

        $this->getCarbonThen()->timestamp = $timestamp;

        return $this;
    }

    protected function _at($time)
    {
        $this->_isTimestamp = true;

        $segments = explode(':', $time);
        $h = (int) $segments[0];
        $m = count($segments) > 1 ? (int) $segments[1] : 0;
        $s = count($segments) > 2 ? (int) $segments[2] : 0;

        $t = $this->getCarbonThen()->setTime($h, $m, $s);

        // if time is past, target next day
        ($this->_carbonNow)->gt($t) ? $t->addDay() : $t;

        return $this;
    }

    protected function _onDay($day)
    {
        $this->_isTimestamp = true;

        $t = $this->getCarbonThen();
        $t->day = $day;

        // if time is past, target next month
        ($this->_carbonNow)->gt($t) ? $t->addMonth() : $t;

        return $this;
    }

    protected function _monday($n = 1)
    {
        $this->_isTimestamp = true;

        $t = $this->getCarbonThen();

        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::MONDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _tuesday($n = 1)
    {
        $this->_isTimestamp = true;

        $t = $this->getCarbonThen();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::TUESDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _wednesday($n = 1)
    {
        $this->_isTimestamp = true;

        $t = $this->getCarbonThen();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::WEDNESDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _thursday($n = 1)
    {
        $this->_isTimestamp = true;

        $t = $this->getCarbonThen();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::THURSDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _friday($n = 1)
    {
        $this->_isTimestamp = true;

        $t = $this->getCarbonThen();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::FRIDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _saturday($n = 1)
    {
        $this->_isTimestamp = true;

        $t = $this->getCarbonThen();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::SATURDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _sunday($n = 1)
    {
        $this->_isTimestamp = true;

        $t = $this->getCarbonThen();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::SUNDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _timezone($timezone)
    {
        $this->getCarbonThen()->setTimezone($timezone);

        return $this;
    }
}
