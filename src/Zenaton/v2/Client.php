<?php

namespace Zenaton;

use Zenaton\v2\Traits\SingletonTrait;
use Zenaton\v2\Workflows\Version;
use Zenaton\v2\Interfaces\WorkflowInterface;
use Zenaton\v2\Interfaces\EventInterface;
use Zenaton\v2\Exceptions\InvalidArgumentException;
use Zenaton\v2\Exception\ConnectionErrorException;
use Zenaton\v2\Services\Http;
use Zenaton\v2\Services\Serializer;
use Zenaton\v2\Services\Properties;

class Client
{
    use SingletonTrait;

    const ZENATON_API_URL = 'https://zenaton.com/api/v1';
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

    const PROG = 'PHP';

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

    public function getWorkerUrl($ressources = '', $params = '')
    {
        $url = (getenv('ZENATON_WORKER_URL') ? : self::ZENATON_WORKER_URL)
            . ':' . (getenv('ZENATON_WORKER_PORT') ? : self::DEFAULT_WORKER_PORT)
            . '/api/' . self::WORKER_API_VERSION
            . '/' . $ressources . '?';

        return $this->addAppEnv($url, $params);
    }

    public function getWebsiteUrl($ressources = '' , $params = '')
    {
        $url = (getenv('ZENATON_API_URL') ? : self::ZENATON_API_URL)
            . '/' . $ressources . '?'
            . self::API_TOKEN.'='.$this->apiToken . '&';

        return $this->addAppEnv($url, $params);
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
        $customId = null;
        if (method_exists($flow, 'getId')) {
            $customId = $flow->getId();
            if (! is_string($customId) && ! is_int($customId)) {
                throw new InvalidArgumentException('Provided Id must be a string or an integer');
            }
            // at the end, it's a string
            $customId = (string) $customId;
            // should be not more than 256 bytes;
            if (strlen($customId) > self::MAX_ID_SIZE ) {
                throw new InvalidArgumentException('Provided Id must not exceed '. self::MAX_ID_SIZE . ' bytes');
            }
        }

        // start workflow
        $this->http->post($this->getInstanceWorkerUrl(), [
            self::ATTR_PROG => self::PROG,
            self::ATTR_CANONICAL => $canonical,
            self::ATTR_NAME => get_class($flow),
            self::ATTR_DATA => $this->serializer->encode($this->properties->getPropertiesFromObject($flow)),
            self::ATTR_ID => $customId
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
        $params =
            self::ATTR_ID.'='.$customId.'&'.
            self::ATTR_NAME.'='.$workflowName.'&'.
            self::ATTR_PROG.'='.self::PROG;

        $data = $this->http->get($this->getInstanceWebsiteUrl($params))->data;

        return $this->properties->getObjectFromNameAndProperties($data->name, $this->serializer->decode($data->properties));
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
        $params = self::ATTR_ID.'='.$customId;
        return $this->http->put($this->getInstanceWorkerUrl($params), [
            self::ATTR_PROG => self::PROG,
            self::ATTR_NAME => $workflowName,
            self::ATTR_MODE => $mode,
        ]);
    }

    protected function getInstanceWebsiteUrl($params)
    {
        return $this->getWebsiteUrl('instances', $params);
    }

    protected function getInstanceWorkerUrl($params = '')
    {
        return $this->getWorkerUrl('instances', $params);
    }

    protected function getSendEventURL()
    {
        return $this->getWorkerUrl('events');
    }

    protected function addAppEnv($url, $params = '')
    {
        // when called from worker, APP_ENV and APP_ID is not defined
        return $url
            . ($this->appEnv ? self::APP_ENV . '=' . $this->appEnv . '&' : '')
            . ($this->appId ? self::APP_ID . '=' . $this->appId . '&' : '')
            . ($params ? $params . '&' : '');
    }
}
