<?php

namespace Zenaton\Worker;

use Exception;
use Zenaton\Common\Services\Jsonizer;
use Zenaton\Common\Services\Http;
use Zenaton\Common\Traits\SingletonTrait;
use Zenaton\Common\Interfaces\WorkflowInterface;

class MicroServer
{
    use SingletonTrait;

    const MICROSERVER_URL = 'http://localhost:4001';
    const ENV_WORKER_PORT = 'ZENATON_WORKER_PORT';

    protected $jsonizer;
    protected $flow;
    protected $http;

    protected $uuid;
    protected $hash;

    public function construct()
    {
        $this->jsonizer = new Jsonizer();
        $this->flow = Workflow::getInstance();
        $this->http = new Http();
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function reset()
    {
        $this->uuid = null;
        $this->hash = null;

        return $this;
    }

    public function isDeciding()
    {
        return (is_null($this->hash) && !is_null($this->uuid));
    }

    public function isWorking()
    {
        return (!is_null($this->hash) && !is_null($this->uuid));
    }

    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    public function askJob($instanceId, $slaveId)
    {
        $url = $this->microServerUrl('/jobs/'. $instanceId.'?slave_id='. $slaveId);

        return $this->http->get($url);
    }

    public function sendEnv($body)
    {
        $url = $this->microServerUrl('/configuration');
        return $this->http->post($url, $body);
    }

    public function stop($body)
    {
        $url = $this->microServerUrl('/stop');
        return $this->http->post($url, $body);
    }

    public function getWorkflowToExecute()
    {
        return $this->sendDecision(['action' => 'start']);
    }

    public function status()
    {
        $url = $this->microServerUrl('/status');
        return $this->http->get($url);
    }

    public function execute($boxes)
    {
        $body['action'] = 'execute';

        foreach ($boxes as $box) {
            $works[] = $box->getWork();
        }
        $body['works'] = $works;

        $response = $this->sendDecision($body);

        if ($response->status === 'completed') {
            // decode properties
            $response->properties = $this->jsonizer->decode($response->properties);

            // decode outputs ($output can be null, eg. wait task)
            $outputs = array_map(function ($output) {
                if (!is_null($output)) {
                    return $this->jsonizer->decode($output);
                }
            }, $response->outputs);

            $response->outputs = (count($outputs) > 1) ? $outputs : $outputs[0];
        }

        return $response;
    }

    public function completeDecision()
    {
        $this->sendDecision([
            'action' => 'terminate',
            'status' => 'running',
            'properties' => $this->flow->getEncodedProperties()
        ]);
    }


    public function completeDecisionBranch($output)
    {
        $this->sendDecision([
            'action' => 'terminate',
            'status' => 'completed',
            'properties' => $this->flow->getEncodedProperties(),
            'output' =>  $this->jsonizer->encode($output)
        ]);
    }

    public function failDecider(Exception $e)
    {
        $this->sendDecision([
            'action' => 'terminate',
            'status' => 'zenatonFailed',
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'error_name' =>  get_class($e),
            'error_stacktrace' => $e->getTraceAsString(),
            'failed_at' => (new \DateTime())->getTimestamp()
        ]);
    }

    public function failDecision(Exception $e)
    {
        $this->sendDecision([
            'action' => 'terminate',
            'status' => 'failed',
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'error_name' =>  get_class($e),
            'error_stacktrace' => $e->getTraceAsString(),
            'failed_at' => (new \DateTime())->getTimestamp()
        ]);
    }

    public function completeWork($output)
    {
        $this->sendWork([
            'action' => 'terminate',
            'status' => 'completed',
            'output' => $this->jsonizer->encode($output),
            'duration' => 0,
        ]);
    }

    public function failWorker(Exception $e)
    {
        $this->sendWork([
            'action' => 'terminate',
            'status' => 'zenatonFailed',
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'error_name' =>  get_class($e),
            'error_stacktrace' => $e->getTraceAsString(),
            'failed_at' => (new \DateTime())->getTimestamp()
        ]);
    }


    public function failWork(Exception $e)
    {
        $this->sendWork([
            'action' => 'terminate',
            'status' => 'failed',
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'error_name' =>  get_class($e),
            'error_stacktrace' => $e->getTraceAsString(),
            'failed_at' => (new \DateTime())->getTimestamp()
        ]);
    }

    public function sendWork($body)
    {
        $url = $this->microServerUrl('/works/'. $this->uuid);

        $body['hash'] = $this->hash;
        return $this->http->post($url, $body);
    }

    public function sendDecision($body)
    {
        $url = $this->microServerUrl('/decisions/'.$this->uuid);
        return $this->http->post($url, $body);
    }

    protected function microServerUrl($ressource)
    {
        $url = getenv(self::ENV_WORKER_PORT) ? 'http://localhost:'.getenv(self::ENV_WORKER_PORT) : self::MICROSERVER_URL;
        return $url.$ressource;
    }
}
