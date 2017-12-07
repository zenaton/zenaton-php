<?php

namespace Zenaton\Engine;

use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\BoxInterface;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Traits\SingletonTrait;
use Zenaton\Client;

class Engine
{
    use SingletonTrait;

    protected $worker;
    protected $client;

    public function construct()
    {
        $this->client = Client::getInstance();

        // zenaton execution
        if (class_exists('Zenaton\Worker\Helpers')) {
            $this->worker = \Zenaton\Worker\Helpers::getInstance();
        }
    }

    public function execute($boxes)
    {
        // check arguments'type
        $this->checkArgumentsType($boxes);

        // local execution
        if (is_null($this->worker)) {
            return $this->doExecute($boxes);
        }

        // zenaton execution
        return $this->worker->doExecute($boxes, true);
    }

    public function dispatch($boxes)
    {
        // check arguments'type
        $this->checkArgumentsType($boxes);

        // local execution
        if (is_null($this->worker)) {
            // dispatch works to Zenaton (only workflows by now)
            foreach ($boxes as $box) {
                $this->client->startWorkflow($box);
            }

            return;
        }

        // zenaton execution
        return $this->worker->doExecute($boxes, false);
    }

    protected function doExecute($boxes)
    {
        $outputs = [];
        foreach ($boxes as $box) {
            $outputs[] = $box->handle();
        }
        // sync executions return results
        return (count($boxes) > 1) ? $outputs : $outputs[0];
    }

    protected function checkArgumentsType($boxes)
    {
        $error = new InvalidArgumentException(
            'arguments MUST be one or many objects implementing '.TaskInterface::class.
            ' or '.WorkflowInterface::class
        );

        // check there is at least one argument
        if (count($boxes) == 0) {
            throw $error;
        }

        // check each arguments'type
        $check = function ($arg) use ($error) {
            if ( ! is_object($arg) || ! $arg instanceof BoxInterface) {
                throw $error;
            }
        };

        array_map($check, $boxes);
    }
}
