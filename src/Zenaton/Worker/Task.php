<?php

namespace Zenaton\Worker;

use Zenaton\Common\Interfaces\TaskInterface;
use Zenaton\Common\Services\Serializer;
use Zenaton\Common\Services\Properties;
use Zenaton\Common\Traits\SingletonTrait;

class Task
{
    use SingletonTrait;

    protected $serializer;
    protected $properties;

    public function construct()
    {
        $this->serializer = new Serializer();
        $this->properties = new Properties();
    }

    public function init($name, $input)
    {
        $this->task = $this->properties->getObjectFromNameAndProperties(
            $name,
            $this->serializer->decode($input),
            TaskInterface::class
        );

        return $this;
    }

    public function handle()
    {
        return $this->task->handle();
    }
}
