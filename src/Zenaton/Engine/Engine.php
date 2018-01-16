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

    protected $client;
    protected $processer;

    public function construct()
    {
        $this->client = Client::getInstance();

        // No processer
        $this->processer = null;
    }

    public function setProcesser($processer) {
		$this->processer = $processer;
	}

    public function execute($jobs)
    {
        // check arguments'type
        $this->checkArguments($jobs);

        // local execution
        if (is_null($this->processer) || count($jobs) == 0) {
            $outputs = [];
            // simply apply handle method
            foreach ($jobs as $job) {
                $outputs[] = $job->handle();
            }
            // return results
            return $outputs;
        }

        // executed by Zenaton worker
        return $this->processer->process($jobs, true);
    }

    public function dispatch($jobs)
    {
        // check arguments'type
        $this->checkArguments($jobs);

        // local execution
        if (is_null($this->processer) || count($jobs) == 0) {
            $outputs = [];
            // dispatch works to Zenaton (only workflows by now)
            foreach ($jobs as $job) {
                if ($job instanceof WorkflowInterface) {
                    $outputs[] = $this->client->startWorkflow($job);
                } elseif ($job instanceof TaskInterface) {
                    $outputs[] = $job->handle();
                } else {
                    throw new InvalidArgumentException("Object to dispatch should implement " . WorkflowInterface::class);
                }
            }
            // return results
            return $outputs;
        }

        // executed by Zenaton worker
        return $this->processer->process($jobs, false);
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
