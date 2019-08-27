<?php

namespace Zenaton;

use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;
use Zenaton\Api\GraphQL\Mutations;
use Zenaton\Api\GraphQL\Queries;
use Zenaton\Exceptions\ApiException;
use Zenaton\Exceptions\ConnectionErrorException;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Interfaces\EventInterface;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Model\Scheduling\Schedule;
use Zenaton\Services\Http;
use Zenaton\Services\Properties;
use Zenaton\Services\Serializer;
use Zenaton\Traits\SingletonTrait;
use Zenaton\Workflows\Version as VersionedWorkflow; // Alias is required because of a bug in PHP 5.6 namespace shadowing. See https://bugs.php.net/bug.php?id=66862.

class Client
{
    use SingletonTrait;

    const ZENATON_API_URL = 'https://api.zenaton.com/v1';
    const ZENATON_ALFRED_URL = 'https://gateway.zenaton.com/api';
    const ZENATON_WORKER_URL = 'http://localhost';
    const DEFAULT_WORKER_PORT = 4001;
    const WORKER_API_VERSION = 'v_newton';

    const MAX_ID_SIZE = 256;

    const APP_ENV = 'app_env';
    const APP_ID = 'app_id';
    const API_TOKEN = 'api_token';

    const ATTR_INTENT_ID = 'intent_id';
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
    const EVENT_DATA = 'event_data';

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
    /** @var UuidFactoryInterface */
    protected $uuidFactory;

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
        $this->uuidFactory = new UuidFactory();
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
     * @throws ApiException If the API returns some errors
     */
    public function startTask(TaskInterface $task)
    {
        $variables = [
            'input' => [
                'environmentName' => $this->appEnv,
                'intentId' => $this->uuidFactory->uuid4()->toString(),
                'maxProcessingTime' => method_exists($task, 'getMaxProcessingTime') ? $task->getMaxProcessingTime() : null,
                'programmingLanguage' => self::PROG,
                'name' => \get_class($task),
                'data' => $this->serializer->encode($this->properties->getPropertiesFromObject($task)),
            ],
        ];

        try {
            $response = $this->http->post($this->getApiUrl(), \json_encode(['query' => Mutations::DISPATCH_TASK, 'variables' => $variables]), [
                'headers' => [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ],
            ]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }

        if (isset($response->body->errors)) {
            throw ApiException::fromErrorList($response->body->errors);
        }
    }

    /**
     * Start a workflow instance.
     *
     * @param WorkflowInterface $flow Workflow to start
     *
     * @throws ApiException                                 if the API returns some errors
     * @throws \Zenaton\Exceptions\InvalidArgumentException if custom id is invalid
     */
    public function startWorkflow(WorkflowInterface $flow)
    {
        $canonical = null;
        if ($flow instanceof VersionedWorkflow) {
            $canonical = \get_class($flow);
            // replace $flow by true current implementation
            $flow = $flow->getCurrentImplementation();
        }

        $customId = null;
        if (method_exists($flow, 'getId')) {
            $customId = $flow->getId();
            if (!is_string($customId) && !is_int($customId)) {
                throw new InvalidArgumentException('Provided Id must be a string or an integer');
            }
            // convert it to a string
            $customId = (string) $customId;
            // should be not more than 256 bytes;
            if (strlen($customId) > self::MAX_ID_SIZE) {
                throw new InvalidArgumentException('Provided Id must not exceed '.self::MAX_ID_SIZE.' bytes');
            }
        }

        $variables = [
            'input' => [
                'customId' => $customId,
                'environmentName' => $this->appEnv,
                'intentId' => $this->uuidFactory->uuid4()->toString(),
                'programmingLanguage' => self::PROG,
                'canonicalName' => $canonical,
                'data' => $this->serializer->encode($this->properties->getPropertiesFromObject($flow)),
                'name' => \get_class($flow),
            ],
        ];

        try {
            $response = $this->http->post($this->getApiUrl(), \json_encode(['query' => Mutations::DISPATCH_WORKFLOW, 'variables' => $variables]), [
                'headers' => [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ],
            ]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }

        if (isset($response->body->errors)) {
            throw ApiException::fromErrorList($response->body->errors);
        }
    }

    /**
     * Kill a workflow instance.
     *
     * @param string $workflowName Workflow class name
     * @param string $customId     Provided custom id
     *
     * @throws ApiException if the API returns some errors
     */
    public function killWorkflow($workflowName, $customId)
    {
        $variables = [
            'input' => [
                'customId' => $customId,
                'environmentName' => $this->appEnv,
                'intentId' => $this->uuidFactory->uuid4()->toString(),
                'programmingLanguage' => self::PROG,
                'name' => $workflowName,
            ],
        ];

        try {
            $response = $this->http->post($this->getApiUrl(), \json_encode(['query' => Mutations::KILL_WORKFLOW, 'variables' => $variables]), [
                'headers' => [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ],
            ]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }

        if (isset($response->body->errors)) {
            throw ApiException::fromErrorList($response->body->errors);
        }
    }

    /**
     * Pause a workflow instance.
     *
     * @param string $workflowName Workflow class name
     * @param string $customId     Provided custom id
     *
     * @throws ApiException if the API returns some errors
     */
    public function pauseWorkflow($workflowName, $customId)
    {
        $variables = [
            'input' => [
                'customId' => $customId,
                'environmentName' => $this->appEnv,
                'intentId' => $this->uuidFactory->uuid4()->toString(),
                'programmingLanguage' => self::PROG,
                'name' => $workflowName,
            ],
        ];

        try {
            $response = $this->http->post($this->getApiUrl(), \json_encode(['query' => Mutations::PAUSE_WORKFLOW, 'variables' => $variables]), [
                'headers' => [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ],
            ]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }

        if (isset($response->body->errors)) {
            throw ApiException::fromErrorList($response->body->errors);
        }
    }

    /**
     * Resume a workflow instance.
     *
     * @param string $workflowName Workflow class name
     * @param string $customId     Provided custom id
     *
     * @throws ApiException if the API returns some errors
     */
    public function resumeWorkflow($workflowName, $customId)
    {
        $variables = [
            'input' => [
                'customId' => $customId,
                'environmentName' => $this->appEnv,
                'intentId' => $this->uuidFactory->uuid4()->toString(),
                'programmingLanguage' => self::PROG,
                'name' => $workflowName,
            ],
        ];

        try {
            $response = $this->http->post($this->getApiUrl(), \json_encode(['query' => Mutations::RESUME_WORKFLOW, 'variables' => $variables]), [
                'headers' => [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ],
            ]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }

        if (isset($response->body->errors)) {
            throw ApiException::fromErrorList($response->body->errors);
        }
    }

    /**
     * Schedule a task instance.
     *
     * @param string $cron
     *
     * @throws ApiException
     */
    public function scheduleTask(TaskInterface $task, $cron)
    {
        $name = \get_class($task);

        $variables = [
            'input' => [
                'environmentName' => $this->appEnv,
                'cron' => $cron,
                'intentId' => $this->uuidFactory->uuid4()->toString(),
                'programmingLanguage' => static::PROG,
                'properties' => $this->serializer->encode($this->properties->getPropertiesFromObject($task)),
                'taskName' => $name,
            ],
        ];

        try {
            $response = $this->http->post($this->getAlfredUrl(), \json_encode(['query' => Mutations::CREATE_TASK_SCHEDULE, 'variables' => $variables]), [
                'headers' => [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ],
            ]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }
        if (isset($response->body->errors)) {
            throw ApiException::fromErrorList($response->body->errors);
        }
    }

    /**
     * Schedule a workflow instance.
     *
     * @param string $cron
     *
     * @throws ApiException
     */
    public function scheduleWorkflow(WorkflowInterface $workflow, $cron)
    {
        $canonicalName = $name = \get_class($workflow);
        if ($workflow instanceof VersionedWorkflow) {
            $workflow = $workflow->getCurrentImplementation();
            $name = \get_class($workflow);
        }

        $variables = [
            'input' => [
                'environmentName' => $this->appEnv,
                'cron' => $cron,
                'canonicalName' => $canonicalName,
                'intentId' => $this->uuidFactory->uuid4()->toString(),
                'programmingLanguage' => static::PROG,
                'properties' => $this->serializer->encode($this->properties->getPropertiesFromObject($workflow)),
                'workflowName' => $name,
            ],
        ];

        try {
            $response = $this->http->post($this->getAlfredUrl(), \json_encode(['query' => Mutations::CREATE_WORKFLOW_SCHEDULE, 'variables' => $variables]), [
                'headers' => [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ],
            ]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }
        if (isset($response->body->errors)) {
            throw ApiException::fromErrorList($response->body->errors);
        }
    }

    /**
     * Find a workflow instance.
     *
     * @param string $workflowName Workflow class name
     * @param string $customId     Provided custom id
     *
     * @throws ApiException if the API returns some errors
     *
     * @return null|WorkflowInterface
     */
    public function findWorkflow($workflowName, $customId)
    {
        $variables = [
            'customId' => $customId,
            'environmentName' => $this->appEnv,
            'programmingLanguage' => self::PROG,
            'workflowName' => $workflowName,
        ];

        try {
            $response = $this->http->post($this->getApiUrl(), \json_encode(['query' => Queries::WORKFLOW, 'variables' => $variables]), [
                'headers' => [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ],
            ]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }

        if (isset($response->body->errors)) {
            // If there is a NOT_FOUND error, we return null
            $notFoundErrors = \array_filter($response->body->errors, static function ($error) {
                return isset($error->type) && $error->type === 'NOT_FOUND';
            });
            if (\count($notFoundErrors) > 0) {
                return null;
            }

            // Otherwise, we generate an exception from the error list
            throw ApiException::fromErrorList($response->body->errors);
        }

        return $this->properties->getObjectFromNameAndProperties($response->body->data->workflow->name, $this->serializer->decode($response->body->data->workflow->properties));
    }

    /**
     * Send an event to a workflow instance.
     *
     * @param string         $workflowName Workflow class name
     * @param string         $customId     Provided custom id
     * @param EventInterface $event        Event to send
     *
     * @throws ApiException if the API returns some errors
     */
    public function sendEvent($workflowName, $customId, EventInterface $event)
    {
        $variables = [
            'input' => [
                'customId' => $customId,
                'environmentName' => $this->appEnv,
                'name' => get_class($event),
                'input' => $this->serializer->encode($this->properties->getPropertiesFromObject($event)),
                'data' => $this->serializer->encode($event),
                'intentId' => $this->uuidFactory->uuid4()->toString(),
                'programmingLanguage' => self::PROG,
                'workflowName' => $workflowName,
            ],
        ];

        try {
            $response = $this->http->post($this->getApiUrl(), \json_encode(['query' => Mutations::SEND_EVENT, 'variables' => $variables]), [
                'headers' => [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ],
            ]);
        } catch (ConnectionErrorException $e) {
            throw ApiException::connectionError($e);
        }

        if (isset($response->body->errors)) {
            throw ApiException::fromErrorList($response->body->errors);
        }
    }

    /**
     * @param string $resource
     *
     * @return string
     */
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

    /**
     * @param string $ressources
     *
     * @return string
     */
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

    /**
     * Returns the URL of the Alfred API.
     *
     * This method will first look at a `ZENATON_ALFRED_URL` environment variable and return its variable if defined.
     * Otherwise, it will return the default production URL.
     *
     * @return string
     */
    protected function getAlfredUrl()
    {
        return \getenv('ZENATON_ALFRED_URL') ?: self::ZENATON_ALFRED_URL;
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

    /**
     * @param string $url
     *
     * @return string
     */
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

    /**
     * Returns the Zenaton API url to send requests to.
     *
     * This will check the ZENATON_API_URL environment variable first if it is defined. Otherwise, it will use the
     * default production url.
     *
     * @return string
     */
    private function getApiUrl()
    {
        return \getenv('ZENATON_API_URL') ?: self::ZENATON_API_URL;
    }
}
