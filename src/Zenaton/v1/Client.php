<?php

namespace Zenaton;

use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;
use Zenaton\Api\GraphQL\Mutations;
use Zenaton\Api\GraphQL\Queries;
use Zenaton\Exceptions\ApiException;
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
    const ZENATON_GATEWAY_URL = 'https://gateway.zenaton.com/api';
    const ZENATON_WORKER_URL = 'http://localhost';
    const DEFAULT_WORKER_PORT = 4001;
    const WORKER_API_VERSION = 'v_newton';

    const MAX_ID_SIZE = 256;

    const APP_ENV = 'app_env';
    const APP_ID = 'app_id';
    const API_TOKEN = 'api_token';

    const PROG = 'PHP';

    /** @var null|string */
    protected $appId;
    /** @var null|string */
    protected $apiToken;
    /** @var null|string */
    protected $appEnv;
    /** @var Api\GraphQL\Client */
    protected $graphqlClient;
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
            ->setAppEnv($appEnv)
        ;
    }

    public function construct()
    {
        $this->graphqlClient = new Api\GraphQL\Client(new Http(), $this->getGatewayUrl(), []);
        $this->serializer = new Serializer();
        $this->properties = new Properties();
        $this->uuidFactory = new UuidFactory();
    }

    /**
     * Set App Id.
     *
     * @param string $appId
     *
     * @return $this
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;

        return $this;
    }

    /**
     * Set Api Token.
     *
     * @param string $apiToken
     *
     * @return $this
     */
    public function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    /**
     * Set App environment.
     *
     * @param string $appEnv
     *
     * @return $this
     */
    public function setAppEnv($appEnv)
    {
        $this->appEnv = $appEnv;

        return $this;
    }

    /**
     * Return the worker url to use for a given resource.
     *
     * @param string       $resource
     * @param array|string $params
     *
     * @return string
     *
     * @internal Used by the Zenaton agent. Should not be called by user code.
     */
    public function getWorkerUrl($resource = '', $params = [])
    {
        if (is_array($params)) {
            return $this->getWorkerUrlV2($resource, $params);
        }

        $url = (getenv('ZENATON_WORKER_URL') ?: self::ZENATON_WORKER_URL)
            .':'.(getenv('ZENATON_WORKER_PORT') ?: self::DEFAULT_WORKER_PORT)
            .'/api/'.self::WORKER_API_VERSION
            .'/'.$resource.'?';

        return $this->addAppEnv($url, $params);
    }

    /**
     * Return the website url to use for a given resource.
     *
     * @param string       $resource
     * @param array|string $params
     *
     * @return string
     *
     * @internal Used by the Zenaton agent. Should not be called by user code.
     */
    public function getWebsiteUrl($resource = '', $params = [])
    {
        if (is_array($params)) {
            return $this->getWebsiteUrlV2($resource, $params);
        }

        $url = $this->getApiUrl()
            .'/'.$resource.'?'
            .self::API_TOKEN.'='.$this->apiToken.'&';

        return $this->addAppEnv($url, $params);
    }

    /**
     * Start a single task.
     *
     * @throws ApiException if the API returns some errors
     */
    public function startTask(TaskInterface $task)
    {
        $this->sendGatewayRequestAndThrowOnErrors(function () use ($task) {
            return $this->graphqlClient->request(
                Mutations::DISPATCH_TASK,
                [
                    'input' => [
                        'environmentName' => $this->appEnv,
                        'intentId' => $this->uuidFactory->uuid4()->toString(),
                        'maxProcessingTime' => method_exists($task, 'getMaxProcessingTime') ? $task->getMaxProcessingTime() : null,
                        'programmingLanguage' => self::PROG,
                        'name' => \get_class($task),
                        'data' => $this->serializer->encode($this->properties->getPropertiesFromObject($task)),
                    ],
                ],
                [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ]
            );
        });
    }

    /**
     * Start a workflow instance.
     *
     * @param WorkflowInterface $flow Workflow to start
     *
     * @throws ApiException             if the API returns some errors
     * @throws InvalidArgumentException if custom id is invalid
     */
    public function startWorkflow(WorkflowInterface $flow)
    {
        $canonical = null;
        if ($flow instanceof VersionedWorkflow) {
            $canonical = \get_class($flow);
            // replace $flow by true current implementation
            $flow = $flow->getCurrentImplementation();
        }
        $customId = $this->getCustomIdFromWorkflow($flow);

        $this->sendGatewayRequestAndThrowOnErrors(function () use ($customId, $canonical, $flow) {
            return $this->graphqlClient->request(
                Mutations::DISPATCH_WORKFLOW,
                [
                    'input' => [
                        'customId' => $customId,
                        'environmentName' => $this->appEnv,
                        'intentId' => $this->uuidFactory->uuid4()->toString(),
                        'programmingLanguage' => self::PROG,
                        'canonicalName' => $canonical,
                        'data' => $this->serializer->encode($this->properties->getPropertiesFromObject($flow)),
                        'name' => \get_class($flow),
                    ],
                ],
                [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ]
            );
        });
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
        $this->sendGatewayRequestAndThrowOnErrors(function () use ($customId, $workflowName) {
            return $this->graphqlClient->request(
                Mutations::KILL_WORKFLOW,
                [
                    'input' => [
                        'customId' => $customId,
                        'environmentName' => $this->appEnv,
                        'intentId' => $this->uuidFactory->uuid4()->toString(),
                        'programmingLanguage' => self::PROG,
                        'name' => $workflowName,
                    ],
                ],
                [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ]
            );
        });
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
        $this->sendGatewayRequestAndThrowOnErrors(function () use ($customId, $workflowName) {
            return $this->graphqlClient->request(
                Mutations::PAUSE_WORKFLOW,
                [
                    'input' => [
                        'customId' => $customId,
                        'environmentName' => $this->appEnv,
                        'intentId' => $this->uuidFactory->uuid4()->toString(),
                        'programmingLanguage' => self::PROG,
                        'name' => $workflowName,
                    ],
                ],
                [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ]
            );
        });
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
        $this->sendGatewayRequestAndThrowOnErrors(function () use ($customId, $workflowName) {
            return $this->graphqlClient->request(
                Mutations::RESUME_WORKFLOW,
                [
                    'input' => [
                        'customId' => $customId,
                        'environmentName' => $this->appEnv,
                        'intentId' => $this->uuidFactory->uuid4()->toString(),
                        'programmingLanguage' => self::PROG,
                        'name' => $workflowName,
                    ],
                ],
                [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ]
            );
        });
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
        $this->sendGatewayRequestAndThrowOnErrors(function () use ($task, $cron) {
            return $this->graphqlClient->request(
                Mutations::CREATE_TASK_SCHEDULE,
                [
                    'input' => [
                        'environmentName' => $this->appEnv,
                        'cron' => $cron,
                        'intentId' => $this->uuidFactory->uuid4()->toString(),
                        'programmingLanguage' => static::PROG,
                        'properties' => $this->serializer->encode($this->properties->getPropertiesFromObject($task)),
                        'taskName' => \get_class($task),
                    ],
                ],
                [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ]
            );
        });
    }

    /**
     * Schedule a workflow instance.
     *
     * @param string $cron
     *
     * @throws ApiException
     * @throws InvalidArgumentException
     */
    public function scheduleWorkflow(WorkflowInterface $workflow, $cron)
    {
        $canonicalName = $name = \get_class($workflow);
        if ($workflow instanceof VersionedWorkflow) {
            $workflow = $workflow->getCurrentImplementation();
            $name = \get_class($workflow);
        }
        $customId = $this->getCustomIdFromWorkflow($workflow);

        $this->sendGatewayRequestAndThrowOnErrors(function () use ($workflow, $canonicalName, $cron, $name, $customId) {
            return $this->graphqlClient->request(
                Mutations::CREATE_WORKFLOW_SCHEDULE,
                [
                    'input' => [
                        'environmentName' => $this->appEnv,
                        'cron' => $cron,
                        'canonicalName' => $canonicalName,
                        'intentId' => $this->uuidFactory->uuid4()->toString(),
                        'customId' => $customId,
                        'programmingLanguage' => static::PROG,
                        'properties' => $this->serializer->encode($this->properties->getPropertiesFromObject($workflow)),
                        'workflowName' => $name,
                    ],
                ],
                [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ]
            );
        });
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
        $response = $this->graphqlClient->request(
            Queries::WORKFLOW,
            [
                'customId' => $customId,
                'environmentName' => $this->appEnv,
                'programmingLanguage' => self::PROG,
                'workflowName' => $workflowName,
            ],
            [
                'App-Id' => $this->appId,
                'Api-Token' => $this->apiToken,
            ]
        );

        if (isset($response['errors'])) {
            // If there is a NOT_FOUND error, we return null
            $notFoundErrors = \array_filter($response['errors'], static function ($error) {
                return isset($error['type']) && 'NOT_FOUND' === $error['type'];
            });
            if (\count($notFoundErrors) > 0) {
                return null;
            }

            // Otherwise, we generate an exception from the error list
            throw ApiException::fromErrorList($response['errors']);
        }

        return $this->properties->getObjectFromNameAndProperties($response['data']['findWorkflow']['name'], $this->serializer->decode($response['data']['findWorkflow']['properties']));
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
        $this->sendGatewayRequestAndThrowOnErrors(function () use ($workflowName, $customId, $event) {
            return $this->graphqlClient->request(
                Mutations::SEND_EVENT,
                [
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
                ],
                [
                    'App-Id' => $this->appId,
                    'Api-Token' => $this->apiToken,
                ]
            );
        });
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
            $this->getApiUrl(),
            $ressources
        );

        $params[static::API_TOKEN] = $this->apiToken;

        return $this->addQueryParams($url, $params);
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

    /**
     * Returns the Zenaton Gateway url to send requests to.
     *
     * This will check the ZENATON_GATEWAY_URL environment variable first if it is defined. Otherwise, it will use the
     * default production url.
     *
     * @return string
     */
    private function getGatewayUrl()
    {
        return \getenv('ZENATON_GATEWAY_URL') ?: self::ZENATON_GATEWAY_URL;
    }

    /**
     * Sends a GraphQL request and throw an exception if there are errors returned by the API.
     *
     * @throws ApiException
     *
     * @return array
     */
    private function sendGatewayRequestAndThrowOnErrors(\Closure $closure)
    {
        $response = $closure();

        if (isset($response['errors'])) {
            throw ApiException::fromErrorList($response['errors']);
        }

        return $response;
    }

    /**
     * Returns the custom id of a workflow.
     *
     * @throws InvalidArgumentException
     *
     * @return null|string
     */
    private function getCustomIdFromWorkflow(WorkflowInterface $workflow)
    {
        if (!\method_exists($workflow, 'getId')) {
            return null;
        }

        $customId = $workflow->getId();
        if (!\is_string($customId) && !\is_int($customId)) {
            throw new InvalidArgumentException('Provided Id must be a string or an integer');
        }
        // convert it to a string
        $customId = (string) $customId;
        // should be not more than 256 bytes;
        if (\strlen($customId) > self::MAX_ID_SIZE) {
            throw new InvalidArgumentException('Provided Id must not exceed '.self::MAX_ID_SIZE.' bytes');
        }

        return $customId;
    }
}
