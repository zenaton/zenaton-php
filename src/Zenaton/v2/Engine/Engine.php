<?php

namespace Zenaton\v2\Engine;

use Zenaton\v2\Exceptions\InvalidArgumentException;
use Zenaton\v2\Interfaces\JobInterface;
use Zenaton\v2\Interfaces\TaskInterface;
use Zenaton\v2\Interfaces\WorkflowInterface;
use Zenaton\v2\Traits\SingletonTrait;
use Zenaton\v2\Client;

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

    public function setProcessor($processor) {
		$this->processor = $processor;
	}

    public function execute($jobs)
    {
        // check arguments'type
        $this->checkArguments($jobs);

        // local execution
        if (is_null($this->processor) || count($jobs) == 0) {
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
        if (is_null($this->processor) || count($jobs) == 0) {
            $outputs = [];
            // dispatch works to Zenaton (only workflows by now)
            foreach ($jobs as $job) {
                if ($job instanceof WorkflowInterface) {
                    $outputs[] = $this->client->startWorkflow($job);
                } elseif ($job instanceof TaskInterface) {
                    // $outputs[] = $this->client->startTask($job);
                    $outputs[] = $job->handle();
                } else {
                    throw new InvalidArgumentException("Object to dispatch should implement " . WorkflowInterface::class);
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
            if ( ! is_object($job) || (! $job instanceof TaskInterface && ! $job instanceof WorkflowInterface)) {
                throw new InvalidArgumentException(
                    'You can only execute or dispatch object implementing '.TaskInterface::class.
                    ' or '.WorkflowInterface::class
                );
            }
        }
    }
}
