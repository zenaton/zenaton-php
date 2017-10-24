<?php

namespace Zenaton\Worker;

use Zenaton\Worker\Decider;
use Zenaton\Worker\Worker;

class Slave
{
    protected $slaveId;
    protected $microserver;

    public function __construct($workerId)
    {
        $this->microserver = MicroServer::getInstance();
        $this->workerId = $workerId;
    }

    public function process()
    {

        $response = $this->microserver->askJob($this->workerId);
        if (isset($response->action) && is_object($response)) {
            switch ($response->action) {
                case 'DecisionScheduled':
                    (new Decider($response->uuid))->launch();
                    break;
                case 'TaskScheduled':
                    (new Worker($response->uuid, $response->name, $response->input, $response->hash))->process();
                    break;
            }
        }
    }

}
