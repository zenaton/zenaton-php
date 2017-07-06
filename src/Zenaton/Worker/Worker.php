<?php

namespace Zenaton\Worker;

use Exception;
use Zenaton\Common\Exceptions\ZenatonException;

class Worker
{
    protected $microserver;
    protected $task;

    public function __construct($uuid, $name, $input, $hash)
    {
        $this->microserver = MicroServer::getInstance()->setUuid($uuid)->setHash($hash);
        $this->task = Task::getInstance()->init($name, $input);
    }

    public function process()
    {
        // do task
        try {
            $output = $this->task->handle();
        } catch (ZenatonException $e) {
            $this->microserver->failWorker($e);
            $this->microserver->reset();
            throw $e;
        } catch (Exception $e) {
            // tell microserver we have an exception
            $this->microserver->failWork($e);
            $this->microserver->reset();
            throw $e;
        }

        // tell microserver we have the result
        $this->microserver->completeWork($output);
        $this->microserver->reset();
    }
}
