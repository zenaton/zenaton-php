<?php

namespace Zenaton\Worker;

use Zenaton\Common\Exceptions\InternalZenatonException;
use Zenaton\Common\Interfaces\BoxInterface;
use Zenaton\Common\Interfaces\TaskInterface;
use Zenaton\Common\Interfaces\WaitInterface;
use Zenaton\Common\Interfaces\WaitWhileInterface;
use Zenaton\Common\Interfaces\WorkflowInterface;
use Zenaton\Common\Services\Jsonizer;
use Zenaton\Common\Traits\IsImplementationOfTrait;

class OutputBox
{
    use IsImplementationOfTrait;

    const ATTRIBUTE_NAME = 'name';
    const ATTRIBUTE_INPUT = 'input';
    const ATTRIBUTE_POSITION = 'position';
    const ATTRIBUTE_EVENT = 'event';
    const ATTRIBUTE_TIMEOUT = 'timeout';
    const ATTRIBUTE_TYPE = 'type';

    const TYPE_TASK = 'task';
    const TYPE_WORKFLOW = 'workflow';
    const TYPE_WAIT = 'wait';
    const TYPE_WHILE = 'while';

    protected $jsonizer;
    protected $name;
    protected $input;
    protected $position;
    protected $event;
    protected $timeout;

    public function __construct(BoxInterface $box)
    {
        $this->jsonizer = new Jsonizer();

        $this->name = get_class($box);
        $this->input = $this->jsonizer->getEncodedPropertiesFromObject($box);

        // if $box has an event
        if (method_exists($box, 'getEvent')) {
            $this->event = $box->getEvent();
        }

        // If the user set a timeout
        if (method_exists($box, 'getTimeout')) {
            // Convert in MiliSeconds
            $this->timeout = $box->getTimeout();
        } else {
            // No task_timeout by default
            $this->timeout = PHP_INT_MAX;
        }

        // if $box is a wait or waitWhile
        if (method_exists($box, 'getTimeoutTimestamp')) {
            $this->timeout = $box->getTimeoutTimestamp();
        }

    }

    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    public function getWork()
    {
        $data = [
            self::ATTRIBUTE_NAME => $this->name,
            self::ATTRIBUTE_POSITION => $this->position,
            self::ATTRIBUTE_INPUT => $this->input,
            self::ATTRIBUTE_TIMEOUT => $this->timeout
        ];

        if ($this->isTask()) {
            $data[self::ATTRIBUTE_TYPE] = self::TYPE_TASK;
        } elseif ($this->isFlow()) {
            $data[self::ATTRIBUTE_TYPE] = self::TYPE_WORKFLOW;
        } elseif ($this->isWait()) {
            $data[self::ATTRIBUTE_TYPE] = self::TYPE_WAIT;
            $data[self::ATTRIBUTE_EVENT] = $this->event;
        } elseif ($this->isWaitWhile()) {
            $data[self::ATTRIBUTE_TYPE] = self::TYPE_WHILE;
            $data[self::ATTRIBUTE_EVENT] = $this->event;
        } else {
            throw new InternalZenatonException('Unknown type');
        }

        return $data;
    }

    public function isFlow()
    {
        return $this->isImplementationOf($this->name, WorkflowInterface::class);
    }

    public function isTask()
    {
        return $this->isImplementationOf($this->name, TaskInterface::class);
    }

    public function isWait()
    {
        return $this->isImplementationOf($this->name, WaitInterface::class);
    }

    public function isWaitWhile()
    {
      return $this->isImplementationOf($this->name, WaitWhileInterface::class);
    }
}
