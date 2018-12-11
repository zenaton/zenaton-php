<?php

namespace Zenaton;

use Zenaton\Exceptions\AgentException;
use Zenaton\Exceptions\AgentNotListeningException;
use Zenaton\Exceptions\AgentUpdateRequiredException;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\EventInterface;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Services\Http;
use Zenaton\Services\Properties;
use Zenaton\Services\Serializer;
use Zenaton\Traits\SingletonTrait;
use Zenaton\Workflows\Version as VersionedWorkflow; // Alias is required because of a bug in PHP 5.6 namespace shadowing. See https://bugs.php.net/bug.php?id=66862.

class Client
{
    use SingletonTrait;

    const ZENATON_API_URL = 'https://api.zenaton.com/v1';
    const ZENATON_WORKER_URL = 'http://localhost';
    const DEFAULT_WORKER_PORT = 4001;
    const WORKER_API_VERSION = 'v_newton';

    const MAX_ID_SIZE = 256;

    const APP_ENV = 'app_env';
    const APP_ID = 'app_id';
    const API_TOKEN = 'api_token';

    const ATTR_ID = 'custom_id';
    const ATTR_NAME = 'name';
    const ATTR_CANONICAL = 'canonical_name';
    const ATTR_DATA = 'data';
    const ATTR_PROG = 'programming_language';
    const ATTR_MODE = 'mode';
    const ATTR_MAX_PROCESSING_TIME = 'maxProcessingTime';

    const PROG = 'PHP';

    const EVENT_INPUT = 'event_input';
    const EVENT_NAME = 'event_name';

    const WORKFLOW_KILL = 'kill';
    const WORKFLOW_PAUSE = 'pause';
    const WORKFLOW_RUN = 'run';

    protected $appId;
    protected $apiToken;
    protected $appEnv;
    /** @var Http */
    protected $http;
    /** @var Serializer */
    protected $serializer;
    /** @var Properties */
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
     * @param string       $ressources
     * @param array|string $params
     *
     * @return string
     *
     * @internal Used by the Zenaton agent. Should not be called by user code.
     */
    public function getWorkerUrl($ressources = '', $params = [])
    {
        if (is_array($params)) {
            return $this->getWorkerUrlV2($ressources, $params);
        }

        $url = (getenv('ZENATON_WORKER_URL') ?: self::ZENATON_WORKER_URL)
            .':'.(getenv('ZENATON_WORKER_PORT') ?: self::DEFAULT_WORKER_PORT)
            .'/api/'.self::WORKER_API_VERSION
            .'/'.$ressources.'?';

        return $this->addAppEnv($url, $params);
    }

    /**
     * @param string       $ressources
     * @param array|string $params
     *
     * @return string
     *
     * @internal Used by the Zenaton agent. Should not be called by user code.
     */
    public function getWebsiteUrl($ressources = '', $params = [])
    {
        if (is_array($params)) {
            return $this->getWebsiteUrlV2($ressources, $params);
        }

        $url = (getenv('ZENATON_API_URL') ?: self::ZENATON_API_URL)
            .'/'.$ressources.'?'
            .self::API_TOKEN.'='.$this->apiToken.'&';

        return $this->addAppEnv($url, $params);
    }

    /**
     * Start a single task.
     *
     * @throws AgentNotListeningException   If the agent is not listening to the application
     * @throws AgentUpdateRequiredException If the agent does not have the minimum required version
     * @throws AgentException               For any other error coming from the agent
     */
    public function startTask(TaskInterface $task)
    {
        $response = $this->http->post($this->getTaskWorkerUrl(), [
            self::ATTR_PROG => self::PROG,
            self::ATTR_NAME => get_class($task),
            self::ATTR_DATA => $this->serializer->encode($this->properties->getPropertiesFromObject($task)),
            self::ATTR_MAX_PROCESSING_TIME => method_exists($task, 'getMaxProcessingTime') ? $task->getMaxProcessingTime() : null,
        ]);

        if ($response->hasErrors()) {
            if (strpos($response->body->error, 'Your worker does not listen') !== false) {
                throw new AgentNotListeningException($this->appId, $this->appEnv);
            }

            if (strpos($response->body->error, 'Unknown version') !== false) {
                throw new AgentUpdateRequiredException('>=0.5.0');
            }

            throw new AgentException($response->body->error);
        }
    }

    /**
     * Start a workflow instance.
     *
     * @param WorkflowInterface $flow Workflow to start
     */
    public function startWorkflow(WorkflowInterface $flow)
    {
        $canonical = null;
        // if $flow is a versionned workflow
        if ($flow instanceof VersionedWorkflow) {
            // store canonical name
            $canonical = get_class($flow);
            // replace by true current implementation
            $flow = $flow->getCurrentImplementation();
        }

        // custom id management
        $customId = null;
        if (method_exists($flow, 'getId')) {
            $customId = $flow->getId();
            if (!is_string($customId) && !is_int($customId)) {
                throw new InvalidArgumentException('Provided Id must be a string or an integer');
            }
            // at the end, it's a string
            $customId = (string) $customId;
            // should be not more than 256 bytes;
            if (strlen($customId) > self::MAX_ID_SIZE) {
                throw new InvalidArgumentException('Provided Id must not exceed '.self::MAX_ID_SIZE.' bytes');
            }
        }

        // start workflow
        $this->http->post($this->getInstanceWorkerUrl(), [
            self::ATTR_PROG => self::PROG,
            self::ATTR_CANONICAL => $canonical,
            self::ATTR_NAME => get_class($flow),
            self::ATTR_DATA => $this->serializer->encode($this->properties->getPropertiesFromObject($flow)),
            self::ATTR_ID => $customId,
        ]);
    }

    /**
     * Kill a workflow instance.
     *
     * @param string $workflowName Workflow class name
     * @param string $customId     Provided custom id
     */
    public function killWorkflow($workflowName, $customId)
    {
        $this->updateInstance($workflowName, $customId, self::WORKFLOW_KILL);
    }

    /**
     * Pause a workflow instance.
     *
     * @param string $workflowName Workflow class name
     * @param string $customId     Provided custom id
     */
    public function pauseWorkflow($workflowName, $customId)
    {
        $this->updateInstance($workflowName, $customId, self::WORKFLOW_PAUSE);
    }

    /**
     * Resume a workflow instance.
     *
     * @param string $workflowName Workflow class name
     * @param string $customId     Provided custom id
     */
    public function resumeWorkflow($workflowName, $customId)
    {
        $this->updateInstance($workflowName, $customId, self::WORKFLOW_RUN);
    }

    /**
     * Find a workflow instance.
     *
     * @param string $workflowName Workflow class name
     * @param string $customId     Provided custom id
     *
     * @return null|WorkflowInterface
     */
    public function findWorkflow($workflowName, $customId)
    {
        $params = [
            static::ATTR_ID => $customId,
            static::ATTR_NAME => $workflowName,
            static::ATTR_PROG => static::PROG,
        ];

        // TODO : Have a better error handling by introducing an object between Client and Http that will
        // return domain exceptions and be able to work with multiple transport layers
        try {
            $response = $this->http->get($this->getInstanceWebsiteUrl($params));
        } catch (\Exception $e) {
            return null;
        }

        if ($response->hasErrors()) {
            return null;
        }

        return $this->properties->getObjectFromNameAndProperties($response->body->data->name, $this->serializer->decode($response->body->data->properties));
    }

    /**
     * Send an event to a workflow instance.
     *
     * @param string         $workflowName Workflow class name
     * @param string         $customId     Provided custom id
     * @param EventInterface $event        Event to send
     */
    public function sendEvent($workflowName, $customId, EventInterface $event)
    {
        $url = $this->getSendEventURL();

        $body = [
            self::ATTR_PROG => self::PROG,
            self::ATTR_NAME => $workflowName,
            self::ATTR_ID => $customId,
            self::EVENT_NAME => get_class($event),
            self::EVENT_INPUT => $this->serializer->encode($this->properties->getPropertiesFromObject($event)),
        ];

        $this->http->post($url, $body);
    }

    protected function updateInstance($workflowName, $customId, $mode)
    {
        $params = [
            self::ATTR_ID => $customId,
        ];

        return $this->http->put($this->getInstanceWorkerUrl($params), [
            self::ATTR_PROG => self::PROG,
            self::ATTR_NAME => $workflowName,
            self::ATTR_MODE => $mode,
        ]);
    }

    private function getWorkerUrlV2($resource = '', array $params = [])
    {
        $url = sprintf(
            '%s:%s/api/%s/%s',
            getenv('ZENATON_WORKER_URL') ?: static::ZENATON_WORKER_URL,
            getenv('ZENATON_WORKER_PORT') ?: static::DEFAULT_WORKER_PORT,
            static::WORKER_API_VERSION,
            $resource
        );

        return $this->addQueryParams($url, $params);
    }

    private function getWebsiteUrlV2($ressources = '', array $params = [])
    {
        $url = sprintf(
            '%s/%s',
            getenv('ZENATON_API_URL') ?: self::ZENATON_API_URL,
            $ressources
        );

        $params[static::API_TOKEN] = $this->apiToken;

        return $this->addQueryParams($url, $params);
    }

    protected function getInstanceWebsiteUrl($params)
    {
        return $this->getWebsiteUrl('instances', $params);
    }

    protected function getInstanceWorkerUrl($params = [])
    {
        return $this->getWorkerUrl('instances', $params);
    }

    protected function getTaskWorkerUrl($params = [])
    {
        return $this->getWorkerUrl('tasks', $params);
    }

    protected function getSendEventURL()
    {
        return $this->getWorkerUrl('events');
    }

    /**
     * @param string $url
     * @param string $params
     *
     * @return string
     *
     * @deprecated 0.3.0 Does not properly encode url parameters.
     */
    protected function addAppEnv($url, $params = '')
    {
        static $triggeredDeprecation = false;
        if (!$triggeredDeprecation) {
            trigger_error('You are running a Zenaton agent version <= 0.4.5 which is using deprecated code. Please consider upgrading your agent.', E_USER_DEPRECATED);
        }
        $triggeredDeprecation = true;

        // when called from worker, APP_ENV and APP_ID is not defined
        return $url
            .($this->appEnv ? self::APP_ENV.'='.$this->appEnv.'&' : '')
            .($this->appId ? self::APP_ID.'='.$this->appId.'&' : '')
            .($params ? $params.'&' : '');
    }

    protected function addQueryParams($url, array $params = [])
    {
        // When called from worker, APP_ENV and APP_ID are not defined
        if ($this->appId) {
            $params[static::APP_ID] = $this->appId;
        }
        if ($this->appEnv) {
            $params[static::APP_ENV] = $this->appEnv;
        }

        return sprintf('%s?%s', $url, http_build_query($params));
    }
}
