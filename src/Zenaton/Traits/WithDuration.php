<?php

namespace Zenaton\Traits;

use Zenaton\Exceptions\InternalZenatonException;
use Cake\Chronos\Chronos;

trait WithDuration
{
    protected $_buffer = [];

    public function _getDuration()
    {
        $now = Chronos::now();
        $then = $now->copy();

        foreach ($this->_buffer as $call) {
            $this->_applyDuration($call[0], $call[1], $then);
        }

        return $now->diffInSeconds($then);
    }

    public function seconds($value = 1)
    {
        $this->_buffer[] = [__FUNCTION__, $value];

        return $this;
    }

    public function minutes($value = 1)
    {
        $this->_buffer[] = [__FUNCTION__, $value];

        return $this;
    }

    public function hours($value = 1)
    {
        $this->_buffer[] = [__FUNCTION__, $value];

        return $this;
    }

    public function days($value = 1)
    {
        $this->_buffer[] = [__FUNCTION__, $value];

        return $this;
    }

    public function weeks($value = 1)
    {
        $this->_buffer[] = [__FUNCTION__, $value];

        return $this;
    }

    public function months($value = 1)
    {
        $this->_buffer[] = [__FUNCTION__, $value];

        return $this;
    }

    public function years($value = 1)
    {
        $this->_buffer[] = [__FUNCTION__, $value];

        return $this;
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
