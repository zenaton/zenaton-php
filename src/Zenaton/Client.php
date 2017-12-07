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

    const EVENT_INPUT = 'event_input';
    const EVENT_NAME = 'event_name';

    protected $appId;
    protected $apiToken;
    protected $appEnv;
    protected $http;
    protected $worker;
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

        // zenaton execution
        if (class_exists('Zenaton\Worker\Helpers')) {
            $this->worker = \Zenaton\Worker\Helpers::getInstance();
        }
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

    public function startWorkflow(WorkflowInterface $flow)
    {
        $canonical = null;
        // in case $flow is a Version
        if ($flow instanceof Version) {
            // get flow canonical name
            $canonical = get_class($flow);
            // get flow real instance
            $flow = $flow->getCurrentImplementation();
        }

        // custom id
        if (method_exists($flow, 'getId')) {
            $customId = $flow->getId();
            if (! is_string($customId) && ! is_int($customId)) {
                throw new InvalidArgumentException('The ID provided must be a string or an integer');
            }
            if (strlen($customId) >= self::SIZE_OF_VARCHAR ) {
                throw new InvalidArgumentException('The ID provided must not exceed 191 characters');
            }
        }

        // start workflow
        return $this->http->post($this->getInstanceUrl(), [
            self::PROGRAMMING_LANGUAGE => self::PHP,
            self::CANONICAL => $canonical,
            self::NAME => get_class($flow),
            self::DATA => $this->serializer->encode($this->properties->getFromObject($flow)),
            self::CUSTOM_ID => isset($customId) ? $customId : null
        ]);
    }

    public function getInstanceDetails($customId, $name)
    {
        $params = self::CUSTOM_ID.'='.$customId.'&'.self::NAME.'='.$name.'&'.self::PROGRAMMING_LANGUAGE.'='.self::PHP;

        return $this->http->get($this->getPropertiesUrl($params));
    }

    public function updateInstance($customId, $workflowName, $mode)
    {
        $params = self::CUSTOM_ID.'='.$customId;
        return $this->http->put($this->getInstanceUrl($params), [
            self::NAME => $workflowName,
            self::PROGRAMMING_LANGUAGE => self::PHP,
            self::MODE => $mode,
        ]);
    }

    public function sendEvent($customerId, $workflowName, $name, $input)
    {
        $url = $this->getSendEventURL();

        $body = [
            self::CUSTOM_ID => $customerId,
            self::EVENT_INPUT => $input,
            self::EVENT_NAME => $name,
            self::NAME => $workflowName,
            self::PROGRAMMING_LANGUAGE => self::PHP,
        ];

        return $this->http->post($url, $body);
    }

    protected function getApiUrl()
    {
        $port = getenv('ZENATON_WORKER_PORT') ?: self::DEFAULT_WORKER_PORT;
        return self::ZENATON_WORKER_URL . $port;
    }

    public function getInstanceUrl($params = '')
    {
        return $this->addIdentification($this->getApiUrl(). '/instances', $params);
    }

    public function getPropertiesUrl($params)
    {
        $url =  getenv('ZENATON_API_URL') ?: self::ZENATON_API_URL;
        return $this->addIdentification($url.'/instances', $params);
    }

    protected function getSendEventURL()
    {
        return $this->addIdentification($this->getApiUrl().'/events');
    }

    protected function addIdentification($url, $params = '')
    {
        return $url.'?'.self::APP_ENV.'='.$this->appEnv.'&'.self::APP_ID.'='.$this->appId.'&'.self::API_TOKEN.'='.$this->apiToken.'&'.$params;
    }
}
