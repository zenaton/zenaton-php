<?php

namespace Zenaton\v2\Traits;

use Zenaton\Exceptions\InternalZenatonException;

use Cake\Chronos\Chronos;

trait WithDuration
{
    protected $_buffer;

    public function _getDuration()
    {
        if (null === $this->_buffer) {
            return null;
        }

        [$now, $then] = $this->_initNowThen();

        foreach ($this->_buffer as $call) {
            $then = $this->_applyDuration($call[0], $call[1], $then);
        }

        return $now->diffInSeconds($then);
    }

    public function seconds($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function minutes($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function hours($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function days($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function weeks($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function months($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    public function years($value = 1)
    {
        $this->_push([__FUNCTION__, $value]);

        return $this;
    }

    protected function _initNowThen() {
        // get setted or current time zone
        $tz = self::$_timezone ? : date_default_timezone_get();

        $now = Chronos::now($tz);
        $then = $now->copy();

        return [$now, $then];
    }

    protected function _push($data) {
		if (null === $this->_buffer) {
			$this->_buffer = [];
		}

		$this->_buffer[] = $data;
    }

    protected function _applyDuration($method, $value, $then) {
        switch ($method) {
            case 'seconds':
                return $then->addSeconds($value);
            case 'minutes':
                return $then->addMinutes($value);
            case 'hours':
                return $then->addHours($value);
            case 'days':
                return $then->addDays($value);
            case 'weeks':
                return $then->addWeeks($value);
            case 'months':
                return $then->addMonths($value);
            case 'years':
                return $then->addYears($value);
            default:
                throw new InternalZenatonException('Unknown methods '.$method);
        }
    }
}
