<?php

namespace Zenaton\Worker;

class Position
{
    const SEPARATOR_ASYNC = 'a';
    const SEPARATOR_PARALLEL = 'p';

    protected $main;
    protected $counter;
    protected $position;

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $this->main = 0;
        $this->counter = 0;
        $this->position = "0";
    }

    public function get()
    {
        return $this->position;
    }

    public function next()
    {
        $this->main++;
        $this->position = strval($this->main);
    }

    public function nextParallel()
    {
        if ($this->isInParallel()) {
            $this->counter++;
        } else {
            $this->main++;
            $this->counter = 0;
        }
        $this->position = $this->main . self::SEPARATOR_PARALLEL . $this->counter;
    }

    public function nextAsync()
    {
        $this->main++;
        $this->position = $this->main . self::SEPARATOR_ASYNC;
    }

    protected function isInParallel()
    {
        return strpos($this->position, self::SEPARATOR_PARALLEL) !== false;
    }
}
