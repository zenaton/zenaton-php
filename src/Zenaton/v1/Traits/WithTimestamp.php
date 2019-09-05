<?php

namespace Zenaton\Traits;

use Cake\Chronos\ChronosInterface;
use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Exceptions\InternalZenatonException;

trait WithTimestamp
{
    use WithDuration;

    public static $_MODE_AT = 'AT';
    public static $_MODE_WEEK_DAY = 'WEEK_DAY';
    public static $_MODE_MONTH_DAY = 'MONTH_DAY';
    public static $_MODE_TIMESTAMP = 'TIMESTAMP';

    protected $_mode;

    /**
     * Return Wait timestamp or duration depending on methods used.
     *
     * @return array [null, duration] or [timestamp, null] or [null, null]
     */
    public function _getTimestampOrDuration()
    {
        if (null === $this->_buffer) {
            return [null, null];
        }

        list($now, $then) = $this->_initNowThen();

        $this->_mode = null;
        // apply buffered methods
        foreach ($this->_buffer as $call) {
            $then = $this->_apply($call[0], $call[1], $now, $then);
        }
        // has user used a method by timestamp?
        $isTimestamp = null !== $this->_mode;
        //return
        if ($isTimestamp) {
            return [$then->timestamp, null];
        }

        return [null, $now->diffInSeconds($then)];
    }

    /**
     * Defined by timestamp (timezone independant).
     *
     * @param int $value
     *
     * @return self
     */
    public function timestamp($value)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function at($value)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function dayOfMonth($value)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function monday($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function tuesday($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function wednesday($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function thursday($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function friday($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function saturday($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function sunday($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    protected function _timestamp($timestamp, $then)
    {
        $this->_setMode(self::$_MODE_TIMESTAMP);

        return $then->timestamp($timestamp);
    }

    protected function _at($time, $now, $then)
    {
        $this->_setMode(self::$_MODE_AT);

        $then = $then->setTimeFromTimeString($time);

        // if time is past, target next day/week/month
        if ($now->gt($then)) {
            switch ($this->_mode) {
                case self::$_MODE_AT:
                    $then = $then->addDay();

                    break;
                case self::$_MODE_WEEK_DAY:
                    $then = $then->addWeek();

                    break;
                case self::$_MODE_MONTH_DAY:
                    $then = $then->addMonth();

                    break;
                default:
                    throw new InternalZenatonException('Unknown mode: '.$this->_mode);
            }
        }

        return $then;
    }

    protected function _dayOfMonth($day, $now, $then)
    {
        $this->_setMode(self::$_MODE_MONTH_DAY);

        $then = $then->day($day);

        if ($now->gte($then) && !$this->sameMonthDayLater($now, $day)) {
            $then = $then->addMonth();
        }

        return $then;
    }

    protected function _weekDay($n, $day, $now, $then)
    {
        $this->_setMode(self::$_MODE_WEEK_DAY);

        if ($this->sameWeekDayLater($now, $day)) {
            --$n;
        }

        list($h, $m, $s) = [$then->hour, $then->minute, $then->second];

        for ($i = 0; $i < $n; ++$i) {
            $then = $then->next($day);
        }

        return $then->setTime($h, $m, $s);
    }

    protected function _apply($method, $value, $now, $then)
    {
        switch ($method) {
            case 'timestamp':
                return $this->_timestamp($value, $then);
            case 'at':
                return $this->_at($value, $now, $then);
            case 'dayOfMonth':
                return $this->_dayOfMonth($value, $now, $then);
            case 'monday':
                return $this->_weekDay($value, ChronosInterface::MONDAY, $now, $then);
            case 'tuesday':
                return $this->_weekDay($value, ChronosInterface::TUESDAY, $now, $then);
            case 'wednesday':
                return $this->_weekDay($value, ChronosInterface::WEDNESDAY, $now, $then);
            case 'thursday':
                return $this->_weekDay($value, ChronosInterface::THURSDAY, $now, $then);
            case 'friday':
                return $this->_weekDay($value, ChronosInterface::FRIDAY, $now, $then);
            case 'saturday':
                return $this->_weekDay($value, ChronosInterface::SATURDAY, $now, $then);
            case 'sunday':
                return $this->_weekDay($value, ChronosInterface::SUNDAY, $now, $then);
            default:
                return $this->_applyDuration($method, $value, $then);
        }
    }

    protected function _setMode($mode)
    {
        // can not apply twice the same method
        if ($mode === $this->_mode) {
            throw new ExternalZenatonException('Incompatible definition in Wait methods');
        }
        // timestamp can only be used alone
        if ((null !== $this->_mode && self::$_MODE_TIMESTAMP === $mode) || (self::$_MODE_TIMESTAMP === $this->_mode)) {
            throw new ExternalZenatonException('Incompatible definition in Wait methods');
        }
        // other mode takes precedence to MODE_AT
        if (null === $this->_mode || self::$_MODE_AT === $this->_mode) {
            $this->_mode = $mode;
        }
    }

    /**
     * @param \Cake\Chronos\Chronos $now
     * @param int                   $day
     *
     * @return bool
     */
    protected function sameMonthDayLater($now, $day)
    {
        return $this->sameMonthDay($now, $day) && $this->later($now);
    }

    /**
     * @param \Cake\Chronos\Chronos $now
     * @param int                   $day
     *
     * @return bool
     */
    protected function sameWeekDayLater($now, $day)
    {
        return $this->sameWeekDay($now, $day) && $this->later($now);
    }

    /**
     * @param \Cake\Chronos\Chronos $now
     * @param int                   $day
     *
     * @return bool
     */
    protected function sameMonthDay($now, $day)
    {
        return $now->day === $day;
    }

    /**
     * @param \Cake\Chronos\Chronos $now
     * @param int                   $day
     *
     * @return bool
     */
    protected function sameWeekDay($now, $day)
    {
        return $now->dayOfWeek === $day;
    }

    /**
     * @param \Cake\Chronos\Chronos $now
     *
     * @return bool
     */
    protected function later($now)
    {
        // Find in the buffer if the method `at` was used to set the time
        $time = array_reduce($this->_buffer, function ($carry, $item) {
            if ('at' === $item[0]) {
                return $item[1];
            }

            return $carry;
        }, null);

        if (null === $time) {
            return false;
        }

        $other = $now->setTimeFromTimeString($time);

        return $now->diffInHours($other, false) > 0;
    }
}
