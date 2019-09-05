<?php

namespace Zenaton\Engine;

use Zenaton\Client;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Traits\SingletonTrait;

/**
 * @internal Should not be called by user code. Use the `Zenatonable` trait instead.
 */
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
        $this->checkArguments($jobs, __METHOD__);

        // local execution
        if (is_null($this->processor) || 0 == count($jobs)) {
            $outputs = [];
            // simply apply handle method
            foreach ($jobs as $job) {
                $outputs[] = $job->handle();
            }

            return $outputs;
        }

        // executed by Zenaton worker
        return $this->processor->process($jobs, true);
    }

    public function dispatch($jobs)
    {
        // check arguments'type
        $this->checkArguments($jobs, __METHOD__);

        // local execution
        if (is_null($this->processor) || 0 == count($jobs)) {
            $outputs = [];
            // dispatch jobs to Zenaton
            foreach ($jobs as $job) {
                if ($this->isWorkflow($job)) {
                    $this->client->startWorkflow($job);
                    $outputs[] = null;
                } elseif ($this->isTask($job)) {
                    $this->client->startTask($job);
                    $outputs[] = null;
                } else {
                    throw new InvalidArgumentException();
                }
            }

            return $outputs;
        }

        // executed by Zenaton worker
        return $this->processor->process($jobs, false);
    }

    public function schedule($jobs, $cron)
    {
        // check arguments' types
        $this->checkArguments($jobs, __METHOD__);

        // local execution
        if (0 === \count($jobs)) {
            return [];
        }

        $outputs = [];
        // schedule jobs
        foreach ($jobs as $job) {
            if ($this->isWorkflow($job)) {
                $outputs[] = $this->client->scheduleWorkflow($job, $cron);
            } elseif ($this->isTask($job)) {
                $outputs[] = $this->client->scheduleTask($job, $cron);
            } else {
                // This should never happen because the call to `::checkArguments()` method at the beginning ensures we won't encounter unknown types at this point.
                throw new \LogicException(\sprintf('Cannot schedule job of type %s.', \is_object($job) ? \get_class($job) : \gettype($job)));
            }
        }

        return $outputs;
    }

    protected function checkArguments($jobs, $method)
    {
        foreach ($jobs as $job) {
            if (!$this->isWorkflow($job) && (!$this->isTask($job))) {
                throw new InvalidArgumentException(\sprintf(
                    'You can only %s a Zenaton Task or Workflow',
                    $method
                ));
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
