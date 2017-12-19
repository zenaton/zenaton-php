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
        $this->checkArguments($jobs);

        // local execution
        if (is_null($this->worker) || count($jobs) == 0) {
            $outputs = [];
            // simply apply handle method
            foreach ($jobs as $job) {
                $outputs[] = $job->handle();
            }
            // return results
            return $outputs;
        }

        // executed by Zenaton worker
        return $this->worker->process($jobs, true);
    }

    public function dispatch($jobs)
    {
        // check arguments'type
        $this->checkArguments($jobs);

        // local execution
        if (is_null($this->worker) || count($jobs) == 0) {
            $outputs = [];
            // dispatch works to Zenaton (only workflows by now)
            foreach ($jobs as $job) {
                $outputs[] = $this->client->startWorkflow($job);
            }
            // return results
            return $outputs;
        }

        // executed by Zenaton worker
        return $this->worker->process($jobs, false);
    }

    protected function checkArguments($jobs)
    {
        $check = function ($arg) {
            if ( ! is_object($arg) || (! $arg instanceof TaskInterface && ! $arg instanceof WorkflowInterface)) {
                throw new InvalidArgumentException(
                    'You can only execute or dispatch only object implementing '.TaskInterface::class.
                    ' or '.WorkflowInterface::class
                );
            }
        };
        array_map($check, $jobs);
    }
}
