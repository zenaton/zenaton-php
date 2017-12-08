<?php

namespace Zenaton\Engine;

use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\JobInterface;
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

        // executed by Zenaton worker
        if (class_exists('Zenaton\Worker\Helpers')) {
            $this->worker = \Zenaton\Worker\Helpers::getInstance();
        }
    }

    public function execute($jobs)
    {
        // check arguments'type
        $this->checkArgumentsType($jobs);

        // local execution
        if (is_null($this->worker)) {
            return $this->doExecute($jobs);
        }

        // executed by Zenaton worker
        return $this->worker->doExecute($jobs, true);
    }

    public function dispatch($jobs)
    {
        // check arguments'type
        $this->checkArgumentsType($jobs);

        // local execution
        if (is_null($this->worker)) {
            // dispatch works to Zenaton (only workflows by now)
            foreach ($jobs as $job) {
                $this->client->startWorkflow($job);
            }

            return;
        }

        // executed by Zenaton worker
        return $this->worker->doExecute($jobs, false);
    }

    protected function doExecute($jobs)
    {
        $outputs = [];
        foreach ($jobs as $job) {
            $outputs[] = $job->handle();
        }
        // sync executions return results
        return (count($jobs) > 1) ? $outputs : $outputs[0];
    }

    protected function checkArgumentsType($jobs)
    {
        $error = new InvalidArgumentException(
            'arguments MUST be one or many objects implementing '.TaskInterface::class.
            ' or '.WorkflowInterface::class
        );

        // check there is at least one argument
        if (count($jobs) == 0) {
            throw $error;
        }

        // check each arguments'type
        $check = function ($arg) use ($error) {
            if ( ! is_object($arg) || ! $arg instanceof JobInterface) {
                throw $error;
            }
        };

        array_map($check, $jobs);
    }
}
