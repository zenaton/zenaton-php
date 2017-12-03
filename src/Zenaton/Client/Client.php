<?php

namespace Zenaton\Client;

use Zenaton\Client\Workflow;

class Client
{
    protected $workflow;
    protected $class;

    public function __construct($appId, $apiToken, $appEnv, $workflow = null)
    {
        $this->workflow = $workflow ?: new Workflow();
        $this->class = null;
        Api::getInstance()->init($appId, $apiToken, $appEnv);
    }

    public function start($flow)
    {
        return $this->workflow->start($flow);
    }

    public function find($class)
    {
        $this->class = $class;
        
        return $this;
    }

    public function byId($customId)
    {
        return $this->workflow->setInstance($customId, $this->class);
    }
}
