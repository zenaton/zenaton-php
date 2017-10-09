<?php

namespace Zenaton\Worker;

use Exception;
use Zenaton\Common\Interfaces\BoxInterface;
use Zenaton\Common\Interfaces\EventInterface;
use Zenaton\Common\Interfaces\WorkflowInterface;
use Zenaton\Common\Services\Serializer;
use Zenaton\Common\Services\Properties;
use Zenaton\Common\Traits\SingletonTrait;

class Workflow
{
    use SingletonTrait;

    protected $serializer;
    protected $properties;
    protected $position;
    protected $counter;

    protected $flow;
    protected $event;

    public function construct()
    {
        $this->serializer = new Serializer();
        $this->properties = new Properties();
        $this->position = new Position();
    }

    public function init($name, $input, $event)
    {
        // build workflow object
        $this->flow = $this->properties->getObjectFromNameAndProperties(
            $name,
            $this->serializer->decode($input),
            WorkflowInterface::class
        );

        // build event
        $this->event = $event ? $this->serializer->decode($event) : null;

        // init position
        $this->position->init();

        return $this;
    }

    public function handle()
    {
        // execute main branch
        if (is_null($this->event)) {
            return $this->flow->handle();
        }

        // else exceute event branch
        if (method_exists($this->flow, 'onEvent')) {
            $this->flow->onEvent($this->event);

            return;
        }
    }

    public function boxCompleted(BoxInterface $box, $output)
    {
        return $this->serializer->decode($output);
    }

    public function getProperties()
    {
        return $this->properties->getFromObject($this->flow);
    }

    public function setProperties($properties)
    {
        $this->properties->setToObject($this->flow, $properties);

        return $this;
    }

    public function getPosition()
    {
        return $this->position->get();
    }

    public function next()
    {
        $this->position->next();
    }

    public function nextParallel()
    {
        $this->position->nextParallel();
    }

    public function nextAsync()
    {
        $this->position->nextAsync();
    }
}
