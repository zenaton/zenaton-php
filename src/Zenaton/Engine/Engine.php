<?php

namespace Zenaton\Engine;

use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\JobInterface;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Traits\SingletonTrait;
use Zenaton\Client;
use Zenaton\Worker;

class Engine
{
    use SingletonTrait;

    protected $worker;
    protected $client;

    public function construct()
    {
        $this->client = Client::getInstance();

        // executed by Zenaton worker
        if (class_exists(Worker::class)) {
            $this->worker = Worker::getInstance();
        }
    }

    public function execute($jobs)
    {
        // check arguments'type
        $this->checkArgumentsType($jobs);

        // local execution
        if (is_null($this->worker)) {
            foreach ($jobs as $job) {
                $outputs[] = $job->handle();
            }
            // return results
            return (count($jobs) > 1) ? $outputs : $outputs[0];
        }

        // executed by Zenaton worker
        return $this->worker->process($jobs, true);
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
            // return nothing
            return;
        }

        // executed by Zenaton worker
        return $this->worker->process($jobs, false);
    }

    protected function checkArgumentsType($jobs)
    {
        $error = new InvalidArgumentException(
            'You can dispatch or execute only objects implementing '.TaskInterface::class.
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
