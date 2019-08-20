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

    private function __construct()
    {
    }

    /**
     * @internal should not be called by user code
     *
     * @return self
     */
    public static function fromArray(array $values = [])
    {
        $target = new static();
        $target->name = (string) $values['name'];
        $target->canonicalName = (string) $values['name'];
        $target->programmingLanguage = (string) $values['programmingLanguage'];
        $target->properties = (new Serializer())->decode($values['properties']);

        return $target;
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
