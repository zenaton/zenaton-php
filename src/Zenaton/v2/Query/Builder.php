<?php

namespace Zenaton\v2\Query;

use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Exceptions\UnknownWorkflowException;

use Zenaton\v2\Client;
use Zenaton\v2\Workflows\Version;
use Zenaton\v2\Interfaces\EventInterface;
use Zenaton\v2\Interfaces\WorkflowInterface;
use Zenaton\v2\Services\Serializer;
use Zenaton\v2\Services\Properties;
use Zenaton\v2\Traits\IsImplementationOfTrait;

class Builder
{
    use IsImplementationOfTrait;

    /**
     * The Zenaton client
     *
     * @var \Zenaton\Client
     */
    protected $client;

    /**
     * The queried class
     *
     * @var string
     */
    protected $class;

    /**
     * The instance custom id
     *
     * @var mixed
     */
    protected $id;

    /**
     * Create a new query builder.
     *
     * @param  String  $class
     * @return self
     */
    public function __construct($class)
    {
        $this->checkIsWorkflow($class);

        $this->class = $class;
        $this->client = Client::getInstance();
    }

    /**
     * Create a new pending job dispatch.
     *
     * @return self
     */
    public function whereId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Retrieve an instance
     *
     * @return Zenaton\Interfaces\WorkflowInterface
     */
    public function find()
    {
        return $this->client->findWorkflow($this->class, $this->id);
    }

    /**
     * Send an event to a workflow instance
     *
     * @return self
     */
    public function send(EventInterface $event)
    {
        $this->client->sendEvent($this->class, $this->id, $event);

        return $this;
    }

    /**
     * Kill a workflow instance
     *
     * @return void
     */
    public function kill()
    {
        $this->client->killWorkflow($this->class, $this->id);

        return $this;
    }

    /**
     * Pause a workflow instance
     *
     * @return void
     */
    public function pause()
    {
        $this->client->pauseWorkflow($this->class, $this->id);

        return $this;
    }

    /**
     * Resume a workflow instance
     *
     * @return void
     */
    public function resume()
    {
        $this->client->resumeWorkflow($this->class, $this->id);

        return $this;
    }

    protected function checkIsWorkflow($class)
    {
        if (! $this->isImplementationOf($class, WorkflowInterface::class)) {
            throw new ExternalZenatonException($class . ' should implements '. WorkflowInterface::class);
        }
    }
}
