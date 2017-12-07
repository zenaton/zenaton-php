<?php

namespace Zenaton\Query;

use Zenaton\Client;
use Zenaton\Workflows\Version;
use Zenaton\Exceptions\ExternalZenatonException;
use Zenaton\Interfaces\EventInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Services\Serializer;
use Zenaton\Services\Properties;
use Zenaton\Traits\IsImplementationOfTrait;

class Builder
{
    use IsImplementationOfTrait;

    const WORKFLOW_KILL = 'kill';
    const WORKFLOW_PAUSE = 'pause';
    const WORKFLOW_RUN = 'run';

    private $api;
    private $serializer;
    private $properties;

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
     * @return void
     */
    public function __construct($class)
    {
        $this->checkIsWorkflow($class);

        $this->class = $class;
        $this->client = Client::getInstance();
        $this->serializer = new Serializer();
        $this->properties = new Properties();
    }

    /**
     * Create a new pending job dispatch.
     *
     * @return void
     */
    public function whereId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Retrieve an instance
     *
     * @return mixed
     */
    public function find()
    {
        $properties = $this->serializer->decode(
            $this->client->getInstanceDetails($this->id, $this->class)->data->properties
        );

        return $this->properties->getObjectFromNameAndProperties($this->class, $properties);
    }

    /**
     * Send an event to an instance
     *
     * @return void
     */
    public function send(EventInterface $event)
    {
        $this->client->sendEvent(
            $this->id,
            $this->class,
            get_class($event),
            $this->serializer->encode($this->properties->getFromObject($event))
        );

        return $this;
    }

    /**
     * Kill an instance
     *
     * @return void
     */
    public function kill()
    {
        $this->client->updateInstance($this->id, $this->class, self::WORKFLOW_KILL);

        return $this;
    }

    /**
     * Pause an instance
     *
     * @return void
     */
    public function pause()
    {
        $this->client->updateInstance($this->id, $this->class, self::WORKFLOW_PAUSE);

        return $this;
    }

    /**
     * Resume an instance
     *
     * @return void
     */
    public function resume()
    {
        $this->client->updateInstance($this->id, $this->class, self::WORKFLOW_RUN);

        return $this;
    }

    protected function checkIsWorkflow($class)
    {
        if (! $this->isImplementationOf($class, WorkflowInterface::class)) {
            throw new ExternalZenatonException($class . ' should implements '. WorkflowInterface::class);
        }
    }
}
