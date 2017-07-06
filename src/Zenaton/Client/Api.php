<?php

namespace Zenaton\Client;

use Zenaton\Common\Services\Http;
use Zenaton\Common\Traits\SingletonTrait;

class Api
{
    use SingletonTrait;

    const ZENATON_URL = 'https://barbouze.fr/api';

    const APP_ENV = 'app_env';
    const APP_ID = 'app_id';
    const API_TOKEN = 'api_token';

    const CUSTOM_ID = 'custom_id';
    const DATA = 'data';
    const PROGRAMMING_LANGUAGE = 'programming_language';
    const PHP = 'PHP';
    const NAME = 'name';
    const STATE = 'state';

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
        return $this->http->post($this->getStartWorkflowURL(), [
            self::CUSTOM_ID => $customId,
            self::DATA => $data,
            self::NAME => $name,
            self::PROGRAMMING_LANGUAGE => self::PHP
        ]);
    }

    public function getInstanceDetails($customerId, $name)
    {
        $params = self::NAME.'='.$name.'?'.self::PROGRAMMING_LANGUAGE.'='.self::PHP;
        return $this->http->get($this->getInstanceDetailsURL($customerId, $params));

    }

    public function updateInstance($customerId, $workflowName,  $state)
    {
        return $this->http->put($this->getInstanceDetailsURL($customerId), [
            self::NAME => $workflowName,
            self::PROGRAMMING_LANGUAGE => self::PHP,
            self::STATE => $state
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
            self::PROGRAMMING_LANGUAGE => self::PHP
        ];

        return $this->http->post($url, $body);
    }

    protected function getStartWorkflowURL()
    {
        return $this->addIdentification(self::ZENATON_URL.'/instances');
    }

    protected function getInstanceDetailsURL($customerId, $params = "")
    {
        return $this->addIdentification(self::ZENATON_URL.'/instances/'.$customerId, $params);
    }

    protected function getSendEventURL()
    {
        return $this->addIdentification(self::ZENATON_URL.'/events');
    }

    protected function addIdentification($url, $params = "")
    {
        return $url.'?'.self::APP_ENV.'='.$this->env.'&'.self::APP_ID.'='.$this->appId.'&'.self::API_TOKEN.'='.$this->apiToken.'&'.$params;
    }
}
