<?php

namespace Zenaton\Worker;

use Exception;
use Zenaton\Common\Exceptions\InternalZenatonException;
use Zenaton\Common\Exceptions\ScheduledBoxException;
use Zenaton\Common\Services\Metrics;

class Decider
{
    protected $microserver;
    protected $flow;
    protected $metrics;

    public function __construct($uuid)
    {
        $this->microserver = MicroServer::getInstance()->setUuid($uuid);
        $this->flow = Workflow::getInstance();
        $this->metrics = Metrics::getInstance();
    }

    public function launch()
    {
        while ($branch = $this->microserver->getWorkflowToExecute()) {
            $this->flow->init($branch->name, $branch->properties, $branch->event);
            $this->process();
        }
        $this->microserver->reset();
    }

    public function process()
    {
        // do workflow
        try {
            $output = $this->flow->handle();

        } catch (ScheduledBoxException $e) {
            $this->microserver->completeDecision();
            return;
        } catch (InternalZenatonException $e) {
            $this->microserver->failDecider($e);

            // terminate this decision
            $this->microserver->reset();
            throw $e;
        } catch (Exception $e) {
            $this->microserver->failDecision($e);

            // terminate this decision
            $this->microserver->reset();
            throw $e;
        }

        // send result to microserver
        $this->microserver->completeDecisionBranch($output);
    }
}
