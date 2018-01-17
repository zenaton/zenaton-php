<?php

namespace Zenaton\v2\Workflows;

use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Services\Properties;
use Zenaton\Services\Serializer;

class WorkflowManager
{
    protected $properties;
    protected $serializer;

    public function __construct()
    {
        $this->properties = new Properties();
        $this->serializer = new Serializer();
    }

    public function getWorkflow($name, $encodedData)
    {
        $data = $this->serializer->decode($encodedData);

        // if provided class is a Version, it means it has been replaced since launched
        if (is_subclass_of($name, Version::class)) {
            $version = $this->properties->getNewInstanceWithoutProperties($name);
            $name = $version->initial();
        }

        // build from name and properties
        return $this->properties->getObjectFromNameAndProperties(
            $this->name,
            $data,
            WorkflowInterface::class
        );
    }

}
