<?php

namespace Zenaton\Common\Traits;

use Carbon\Carbon;

trait WithTimeout
{
    use WithDuration;

    protected function _timestamp($timestamp)
    {
        $this->getTimeoutCarbon()->timestamp = $timestamp;

        return $this;
    }

    protected function _at($time)
    {
        $segments = explode(':', $time);
        $h = (int) $segments[0];
        $m = count($segments) > 1 ? (int) $segments[1] : 0;
        $s = count($segments) > 2 ? (int) $segments[2] : 0;

        $t = $this->getTimeoutCarbon()->setTime($h, $m, $s);

        // if time is past, target next day
        ($this->timeoutNow)->gt($t) ? $t->addDay() : $t;

        return $this;
    }

    protected function _onDay($day)
    {
        $t = $this->getTimeoutCarbon();
        $t->day = $day;

        // if time is past, target next month
        ($this->timeoutNow)->gt($t) ? $t->addMonth() : $t;

        return $this;
    }

    protected function _monday($n = 1)
    {
        $t = $this->getTimeoutCarbon();

        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::MONDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _tuesday($n = 1)
    {
        $t = $this->getTimeoutCarbon();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::TUESDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _wednesday($n = 1)
    {
        $t = $this->getTimeoutCarbon();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::WEDNESDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _thursday($n = 1)
    {
        $t = $this->getTimeoutCarbon();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::THURSDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _friday($n = 1)
    {
        $t = $this->getTimeoutCarbon();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::FRIDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _saturday($n = 1)
    {
        $t = $this->getTimeoutCarbon();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::SATURDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _sunday($n = 1)
    {
        $t = $this->getTimeoutCarbon();
        list($h, $m, $s) = [$t->hour, $t->minute, $t->second];
        $t->previous(Carbon::SUNDAY)->addWeeks($n)->setTime($h, $m, $s);

        return $this;
    }

    protected function _timezone($timezone)
    {
        $this->getTimeoutCarbon()->setTimezone($timezone);

        return $this;
    }
}
