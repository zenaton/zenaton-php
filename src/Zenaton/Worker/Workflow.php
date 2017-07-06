<?php

namespace Zenaton\Worker;

use Exception;
use Zenaton\Common\Interfaces\BoxInterface;
use Zenaton\Common\Interfaces\EventInterface;
use Zenaton\Common\Interfaces\WorkflowInterface;
use Zenaton\Common\Services\Jsonizer;
use Zenaton\Common\Traits\SingletonTrait;

class Workflow
{
    use SingletonTrait;

    protected $jsonizer;
    protected $position;
    protected $counter;

    protected $flow;
    protected $event;

    public function construct()
    {
        $this->jsonizer = new Jsonizer();
        $this->position = new Position();
    }

    public function init($name, $input, $event)
    {
        // build workflow object
        $this->flow = $this->jsonizer->getObjectFromNameAndEncodedProperties(
            $name,
            $input,
            WorkflowInterface::class
        );

        // build event
        $this->event = $event ? $this->jsonizer->decode(
            $event,
            EventInterface::class
          ) : null;

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
        return $this->jsonizer->decode($output);
    }

    public function getEncodedProperties()
    {
        return $this->jsonizer->getEncodedPropertiesFromObject($this->flow);
    }

    public function setProperties($properties)
    {
        $this->jsonizer->setPropertiesToObject($this->flow, $properties);

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
