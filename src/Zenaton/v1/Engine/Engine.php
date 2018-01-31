<?php

namespace Zenaton\Engine;

use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Traits\SingletonTrait;
use Zenaton\Client;

class Engine
{
    use SingletonTrait;

    protected $client;
    protected $processor;

    public function construct()
    {
        $this->client = Client::getInstance();

        // No processor
        $this->processor = null;
    }

    public function setProcessor($processor)
    {
        $this->processor = $processor;
    }

    public function execute($jobs)
    {
        // check arguments'type
        $this->checkArguments($jobs);

        // local execution
        if (is_null($this->processor) || 0 == count($jobs)) {
            $outputs = [];
            // simply apply handle method
            foreach ($jobs as $job) {
                $outputs[] = $job->handle();
            }
            // return results
            return $outputs;
        }

        // executed by Zenaton worker
        return $this->processor->process($jobs, true);
    }

    public function dispatch($jobs)
    {
        // check arguments'type
        $this->checkArguments($jobs);

        // local execution
        if (is_null($this->processor) || 0 == count($jobs)) {
            $outputs = [];
            // dispatch works to Zenaton (only workflows by now)
            foreach ($jobs as $job) {
                if ($this->isWorkflow($job)) {
                    $this->client->startWorkflow($job);
                    $outputs[] = null;
                } elseif ($this->isTask($job)) {
                    // $outputs[] = $this->client->startTask($job);
                    $job->handle();
                    $outputs[] = null;
                } else {
                    throw new InvalidArgumentException();
                }
            }
            // return results
            return $outputs;
        }

        // executed by Zenaton worker
        return $this->processor->process($jobs, false);
    }

    protected function checkArguments($jobs)
    {
        foreach ($jobs as $job) {
            if (!$this->isWorkflow($job) && (!$this->isTask($job))) {
                throw new InvalidArgumentException(
                    'You can only execute or dispatch Zenaton Task or Workflow'
                );
            }
        }
    }

    protected function isWorkflow($job)
    {
        return is_object($job) && $job instanceof WorkflowInterface;
    }

    protected function isTask($job)
    {
        return is_object($job) && $job instanceof TaskInterface;
    }
}
