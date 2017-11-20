<?php

namespace Zenaton\Client;

use Zenaton\Common\Services\Http;
use Zenaton\Common\Traits\SingletonTrait;

class Api
{
    use SingletonTrait;

    const ZENATON_API_URL = 'https://zenaton.com/api';
    const ZENATON_WORKER_URL = 'https://localhost:';
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

    const EVENT_INPUT = 'event_input';
    const EVENT_NAME = 'event_name';

    public $appId;
    public $apiToken;
    public $env;

    public function construct()
    {
        $this->http = new Http();
    }

    public function init($appId, $apiToken, $env)
    {
        $this->appId = $appId;
        $this->apiToken = $apiToken;
        $this->env = $env;

        return $this;
    }

    public function startWorkflow($name, $data, $customId)
    {
        return $this->http->post($this->getInstanceUrl(), [
            self::CUSTOM_ID => $customId,
            self::DATA => $data,
            self::NAME => $name,
            self::PROGRAMMING_LANGUAGE => self::PHP,
        ]);
    }

    public function getInstanceDetails($customId, $name)
    {
        $params = self::CUSTOM_ID.'='.$customId.'&'.self::NAME.'='.$name.'&'.self::PROGRAMMING_LANGUAGE.'='.self::PHP;

        return $this->http->get($this->getInstanceUrl($params));
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
        const port = getenv('ZENATON_API_URL') ? : self::DEFAULT_WORKER_PORT;
        return self::ZENATON_WORKER_URL . port;
    }

    public function getInstanceUrl($params = '')
    {
        return $this->addIdentification($this->getApiUrl(). '/instances', $params);
    }

    protected function getSendEventURL()
    {
        return $this->addIdentification($this->getApiUrl().'/events');
    }

    protected function addIdentification($url, $params = '')
    {
        return $url.'?'.self::APP_ENV.'='.$this->env.'&'.self::APP_ID.'='.$this->appId.'&'.self::API_TOKEN.'='.$this->apiToken.'&'.$params;
    }
}
