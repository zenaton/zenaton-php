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

        // if time is past, target next month
        if ($now->gt($then)) {
            $then = $then->addMonth();
        }

        return $then;
    }

    protected function _weekDay($n, $day, $then)
    {
        $this->_setMode(self::$_MODE_WEEK_DAY);

        list($h, $m, $s) = [$then->hour, $then->minute, $then->second];

        return $then->previous($day)->addWeeks($n)->setTime($h, $m, $s);
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
                return $this->_weekDay($value, ChronosInterface::MONDAY, $then);
            case 'tuesday':
                return $this->_weekDay($value, ChronosInterface::TUESDAY, $then);
            case 'wednesday':
                return $this->_weekDay($value, ChronosInterface::WEDNESDAY, $then);
            case 'thursday':
                return $this->_weekDay($value, ChronosInterface::THURSDAY, $then);
            case 'friday':
                return $this->_weekDay($value, ChronosInterface::FRIDAY, $then);
            case 'saturday':
                return $this->_weekDay($value, ChronosInterface::SATURDAY, $then);
            case 'sunday':
                return $this->_weekDay($value, ChronosInterface::SUNDAY, $then);
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
}
