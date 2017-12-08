<?php

namespace Zenaton;

use Zenaton\Traits\SingletonTrait;
use Zenaton\Worker\Version;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Services\Http;
use Zenaton\Services\Serializer;
use Zenaton\Services\Properties;

class Client
{
    use SingletonTrait;

    const ZENATON_API_URL = 'https://zenaton.com/api';
    const ZENATON_WORKER_URL = 'http://localhost:';
    const DEFAULT_WORKER_PORT = 4001;

    const APP_ENV = 'app_env';
    const APP_ID = 'app_id';
    const API_TOKEN = 'api_token';

    const CUSTOM_ID = 'custom_id';
    const DATA = 'data';
    const PROGRAMMING_LANGUAGE = 'programming_language';
    const PHP = 'PHP';
    const NAME = 'name';
    const MODE = 'mode';
    const CANONICAL = 'canonical_name';
    const VARCHAR_MAX_SIZE = 191;

    const EVENT_INPUT = 'event_input';
    const EVENT_NAME = 'event_name';

    const WORKFLOW_KILL = 'kill';
    const WORKFLOW_PAUSE = 'pause';
    const WORKFLOW_RUN = 'run';

    protected $appId;
    protected $apiToken;
    protected $appEnv;
    protected $http;
    protected $serializer;
    protected $properties;

    public static function init($appId, $apiToken, $appEnv)
    {
        Client::getInstance()
          ->setAppId($appId)
          ->setApiToken($apiToken)
          ->setAppEnv($appEnv);
    }

    public function construct()
    {
        $this->http = new Http();
        $this->serializer = new Serializer();
        $this->properties = new Properties();
    }

    public function setAppId($appId)
    {
        $this->appId = $appId;

        return $this;
    }

    public function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function setAppEnv($appEnv)
    {
        $this->appEnv = $appEnv;

        return $this;
    }

    /**
     * Start a workflow instance
     *
     * @param  Zenaton\Interfaces\WorkflowInterface  $flow Workflow to start
     * @return void
     */
    public function startWorkflow(WorkflowInterface $flow)
    {
        $canonical = null;
        // if $flow is a versionned workflow
        if ($flow instanceof Version) {
            // store canonical name
            $canonical = get_class($flow);
            // replace by true current implementation
            $flow = $flow->getCurrentImplementation();
        }

        // custom id management
        if (method_exists($flow, 'getId')) {
            $customId = $flow->getId();
            if (! is_string($customId) && ! is_int($customId)) {
                throw new InvalidArgumentException('Provided Id must be a string or an integer');
            }
            if (strlen($customId) > self::VARCHAR_MAX_SIZE ) {
                throw new InvalidArgumentException('Provided Id must not exceed 191 characters');
            }
        }

        // start workflow
        $this->http->post($this->getInstanceWorkerUrl(), [
            self::PROGRAMMING_LANGUAGE => self::PHP,
            self::CANONICAL => $canonical,
            self::NAME => get_class($flow),
            self::DATA => $this->serializer->encode($this->properties->getFromObject($flow)),
            self::CUSTOM_ID => isset($customId) ? $customId : null
        ]);
    }

    /**
     * Kill a workflow instance
     *
     * @param  String  $workflowName Workflow class name
     * @param  String  $customId     Provided custom id
     * @return void
     */
    public function killWorkflow($workflowName, $customId)
    {
        $this->updateInstance($workflowName, $customId, self::WORKFLOW_KILL);
    }

    /**
     * Pause a workflow instance
     *
     * @param  String  $workflowName Workflow class name
     * @param  String  $customId     Provided custom id
     * @return void
     */
    public function pauseWorkflow($workflowName, $customId)
    {
        $this->updateInstance($workflowName, $customId, self::WORKFLOW_PAUSE);
    }

    /**
     * Resume a workflow instance
     *
     * @param  String  $workflowName Workflow class name
     * @param  String  $customId     Provided custom id
     * @return void
     */
    public function resumeWorkflow($workflowName, $customId)
    {
        $this->updateInstance($workflowName, $customId, self::WORKFLOW_RUN);
    }

    /**
     * Find a workflow instance
     *
     * @param  String  $workflowName Workflow class name
     * @param  String  $customId     Provided custom id
     * @return Zenaton\Interfaces\WorkflowInterface
     */
    public function findWorkflow($workflowName, $customId)
    {
        $properties = $this->getWorkflowProperties($workflowName, $customId);

        return $this->properties->getObjectFromNameAndProperties($workflowName, $properties);
    }

    /**
     * Send an event to a workflow instance
     *
     * @param  String  $workflowName Workflow class name
     * @param  String  $customId     Provided custom id
     * @param  Zenaton\Interfaces\EventInterface $event Event to send
     * @return void
     */
    public function sendEvent($workflowName, $customId, EventInterface $event)
    {
        $url = $this->getSendEventURL();

        $body = [
            self::NAME => $workflowName,
            self::CUSTOM_ID => $customId,
            self::EVENT_NAME => get_class($event),
            self::EVENT_INPUT => $this->serializer->encode($this->properties->getFromObject($event)),
            self::PROGRAMMING_LANGUAGE => self::PHP,
        ];

        $this->http->post($url, $body);
    }

    protected function updateInstance($workflowName, $customId, $mode)
    {
        $params = self::CUSTOM_ID.'='.$customId;
        return $this->http->put($this->getInstanceWorkerUrl($params), [
            self::NAME => $workflowName,
            self::PROGRAMMING_LANGUAGE => self::PHP,
            self::MODE => $mode,
        ]);
    }

    protected function getWorkflowProperties($workflowName, $customId)
    {
        $params = self::CUSTOM_ID.'='.$customId.'&'.self::NAME.'='.$workflowName.'&'.self::PROGRAMMING_LANGUAGE.'='.self::PHP;

        $encoded = $this->http->get($this->getInstanceZenatonUrl($params));

        return $this->serializer->decode($encoded->data->properties);
    }

    protected function getWorkerUrl()
    {
        return self::ZENATON_WORKER_URL . (getenv('ZENATON_WORKER_PORT') ? : self::DEFAULT_WORKER_PORT);
    }

    protected function getZenatonUrl()
    {
        return getenv('ZENATON_API_URL') ? : self::ZENATON_API_URL;
    }

    protected function getInstanceZenatonUrl($params)
    {
        return $this->addIdentification($this->getZenatonUrl().'/instances', $params);
    }

    protected function getInstanceWorkerUrl($params = '')
    {
        return $this->addIdentification($this->getWorkerUrl(). '/instances', $params);
    }

    protected function getSendEventURL()
    {
        return $this->addIdentification($this->getWorkerUrl().'/events');
    }

    protected function addIdentification($url, $params = '')
    {
        return $url
            .'?'.self::APP_ENV.'='.$this->appEnv
            .'&'.self::APP_ID.'='.$this->appId
            .'&'.self::API_TOKEN.'='.$this->apiToken
            .'&'.$params;
    }
}
