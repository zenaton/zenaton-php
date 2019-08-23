<?php

namespace Zenaton\Model\Scheduling;

use Zenaton\Services\Serializer;

final class WorkflowTarget
{
    /** @var string */
    private $canonicalName;
    /** @var string */
    private $name;
    /** @var string */
    private $programmingLanguage;
    /** @var array */
    private $properties;

    public function __construct(array $values)
    {
        $this->name = (string) $values['name'];
        $this->canonicalName = (string) $values['canonicalName'];
        $this->programmingLanguage = (string) $values['programmingLanguage'];
        $this->properties = (new Serializer())->decode($values['properties']);
    }

    /**
     * @return string
     */
    public function getCanonicalName()
    {
        return $this->canonicalName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getProgrammingLanguage()
    {
        return $this->programmingLanguage;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }
}
