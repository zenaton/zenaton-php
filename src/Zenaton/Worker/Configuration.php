<?php

namespace Zenaton\Worker;

use Dotenv\Dotenv;
use Zenaton\Common\Traits\IsImplementationOfTrait;
use Zenaton\Common\Exceptions\ExternalZenatonException;
use Zenaton\Common\Interfaces\TaskInterface;
use Zenaton\Common\Interfaces\WorkflowInterface;

class Configuration
{
    use IsImplementationOfTrait;

    const ENV_API_TOKEN = 'ZENATON_API_TOKEN';
    const ENV_APP_ID = 'ZENATON_APP_ID';
    const ENV_APP_ENV = 'ZENATON_APP_ENV';
    const ENV_HANDLE_ONLY = 'ZENATON_HANDLE_ONLY';
    const ENV_HANDLE_EXCEPT = 'ZENATON_HANDLE_EXCEPT';
    const ENV_CONCURRENT_MAX = 'ZENATON_CONCURRENT_MAX';

    const MS_APP_ID = 'app_id';
    const MS_API_TOKEN = 'api_token';
    const MS_APP_ENV = 'app_env';

    const MS_WORKFLOWS_NAME_ONLY = 'workflows_name_only';
    const MS_TASKS_NAME_ONLY = 'tasks_name_only';

    const MS_WORKFLOWS_NAME_EXCEPT = 'workflows_name_except';
    const MS_TASKS_NAME_EXCEPT = 'tasks_name_except';

    const MS_CONCURRENT_MAX = 'concurrent_max';
    const WORKER_SCRIPT = 'worker_script';
    const AUTOLOAD_PATH = 'autoload_path';
    const PROGRAMMING_LANGUAGE = 'programming_language';
    const PHP = 'PHP';

    protected $envPath;
    protected $microserver;
    protected $autoload;


    public function __construct($envPath = null, $autoload = null)
    {
        $this->envPath = $envPath;
        $this->autoload = $autoload;
        $this->microserver = MicroServer::getInstance();
    }

    public function startMicroserver()
    {

        $this->checkCredentials();

        if(getenv(self::ENV_HANDLE_ONLY)) {
            list($workflowsNamesOnly, $tasksNamesOnly) = $this->verifyClass(getenv(self::ENV_HANDLE_ONLY));
        }

        if(getenv(self::ENV_HANDLE_EXCEPT)) {
            list($workflowsNamesToExcept, $tasksNamesToExcept) = $this->verifyClass(getenv(self::ENV_HANDLE_EXCEPT));
        }

        if(getenv(self::ENV_CONCURRENT_MAX)) {
            $concurrentMax = getenv(self::ENV_CONCURRENT_MAX);
        }

        $body = [
            self::MS_APP_ID => getenv(self::ENV_APP_ID),
            self::MS_API_TOKEN => getenv(self::ENV_API_TOKEN),
            self::MS_APP_ENV => getenv(self::ENV_APP_ENV),
            self::MS_CONCURRENT_MAX => isset($concurrentMax) ? intval($concurrentMax) : 100,
            self::MS_WORKFLOWS_NAME_ONLY => isset($workflowsNamesOnly) ? $workflowsNamesOnly : [],
            self::MS_TASKS_NAME_ONLY => isset($tasksNamesOnly) ? $tasksNamesOnly : [],
            self::MS_WORKFLOWS_NAME_EXCEPT => isset($workflowsNamesToExcept) ? $workflowsNamesToExcept : [],
            self::MS_TASKS_NAME_EXCEPT => isset($tasksNamesToExcept) ? $tasksNamesToExcept : [],
            self::WORKER_SCRIPT => getcwd(). '/vendor/zenaton/zenaton-php/scripts/slave.php',
            self::AUTOLOAD_PATH => getcwd(). '/'. $this->autoload,
            self::PROGRAMMING_LANGUAGE => self::PHP
        ];

        return $this->microserver->sendEnv($body);
    }

    public function status()
    {
        return $this->microserver->status();
    }

    public function stopMicroserver()
    {
        $this->checkCredentials();

        $body = [
            self::MS_APP_ID => getenv(self::ENV_APP_ID),
            self::MS_API_TOKEN => getenv(self::ENV_API_TOKEN),
            self::MS_APP_ENV => getenv(self::ENV_APP_ENV),
            self::PROGRAMMING_LANGUAGE => self::PHP
        ];

        return $this->microserver->stop($body);
    }

    protected function checkCredentials()
    {
        (new Dotenv(dirname($this->envPath), basename($this->envPath)))->load();

        if ( ! getenv(self::ENV_APP_ID)) {
            echo 'Error! Environment variable '.self::ENV_APP_ID.' not set';
            die();
        }

        if ( ! getenv(self::ENV_API_TOKEN)) {
            echo 'Error! Environment variable '.self::ENV_API_TOKEN.' not set';
            die();
        }

        if ( ! getenv(self::ENV_APP_ENV)) {
            echo 'Error! Environment variable '.self::ENV_APP_ENV.' not set';
            die();
        }
    }

    protected function verifyClass($request)
    {
        $workflowsNames = [];
        $tasksNames = [];
        $classes = array_map('trim', explode(',', $request));

        foreach ($classes as $key => $class) {
            if($this->isImplementationOf($class, WorkflowInterface::class)) {
                $workflowsNames[] = $class;
            } elseif ($this->isImplementationOf($class, TaskInterface::class)) {
                $tasksNames[] = $class;
            } else {
                throw new ExternalZenatonException('Invalid class name provided at key '.$key.' - must implement '.WorkflowInterface::class.' or '.TaskInterface::class);
            }
        }

        return array($workflowsNames, $tasksNames);
    }
}
