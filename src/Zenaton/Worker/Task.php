<?php

namespace Zenaton\Worker;

use Zenaton\Common\Interfaces\TaskInterface;
use Zenaton\Common\Services\Jsonizer;
use Zenaton\Common\Traits\SingletonTrait;

class Task
{
    use SingletonTrait;

    protected $jsonizer;

    public function construct()
    {
        $this->jsonizer = new Jsonizer();
    }

    public function init($name, $input)
    {
        $this->task = $this->jsonizer->getObjectFromNameAndEncodedProperties(
            $name,
            $input,
            TaskInterface::class
        );

        return $this;
    }

    public function handle()
    {
        return $this->task->handle();
    }
}
