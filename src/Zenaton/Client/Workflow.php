<?php

namespace Zenaton\Client;

use Zenaton\Client\Api;
use Zenaton\Worker\Version;

use Zenaton\Common\Exceptions\ExternalZenatonException;
use Zenaton\Common\Interfaces\EventInterface;
use Zenaton\Common\Interfaces\WorkflowInterface;
use Zenaton\Common\Services\Serializer;
use Zenaton\Common\Services\Properties;

class Workflow
{
    private $id;
    private $api;
    private $serializer;
    private $properties;
    private $workflowName;

    const SIZE_OF_VARCHAR = 191;

    const KILL = 'kill';
    const PAUSE = 'pause';
    const RUN = 'run';

    public function __construct($id = null, $workflowName = null)
    {
        $this->id = $id;
        $this->workflowName = $workflowName;
        $this->api = Api::getInstance();
        $this->serializer = new Serializer();
        $this->properties = new Properties();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setClass($class)
    {
        $this->class = $class;

        return $this;
    }

    public function start($flow)
    {
        $canonical = null;

        // in case $flow is a Version
        if (is_object($flow) && $flow instanceof Version) {
            // get flow canonical name
            $canonical = get_class($flow);
            // get flow real instance
            $flow = $flow->getCurrentInstance();
        }

        // check this is actually a workflow
        if ( ! is_object($flow) || ! $flow instanceof WorkflowInterface) {
            throw new ExternalZenatonException('First argument must an object implementing '.WorkflowInterface::class);
        }

        if (method_exists($flow, 'getId')) {
            $customId = $flow->getId();

            if (! is_string($customId) && ! is_int($customId)) {
                throw new ExternalZenatonException('The ID provided must be a string or an integer');
            }

            if (strlen($customId) >= self::SIZE_OF_VARCHAR ) {
                throw new ExternalZenatonException('The ID provided must not exceed 191 characters');
            }
        }

        // start workflow
        return $this->api->startWorkflow(
            get_class($flow),
            $this->serializer->encode($this->properties->getFromObject($flow)),
            isset($customId) ? $customId : null,
            $canonical
        );
    }

    public function setInstance($customId, $class)
    {
        return $this->newInstance($customId, $class);
    }

    public function sendEvent(EventInterface $event, $options = [])
    {
        return $this->api->sendEvent(
            $this->id,
            $this->workflowName,
            get_class($event),
            $this->serializer->encode($this->properties->getFromObject($event))
        );
    }

    public function kill()
    {
        return $this->api->updateInstance($this->id, $this->workflowName, self::KILL);
    }

    public function pause()
    {
        return $this->api->updateInstance($this->id, $this->workflowName, self::PAUSE);
    }

    public function resume()
    {
        return $this->api->updateInstance($this->id, $this->workflowName, self::RUN);
    }

    public function getProperties()
    {
        $res = $this->api->getInstanceDetails($this->id, $this->workflowName);
        return $this->serializer->decode($res->data->properties);
    }

    protected function newInstance($id, $workflowName)
    {
        return new self($id, $workflowName);
    }
}
