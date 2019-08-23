<?php

namespace Zenaton\Model\Scheduling;

use Zenaton\Services\Serializer;

final class TaskTarget
{
    /** @var string */
    private $name;
    /** @var string */
    private $programmingLanguage;
    /** @var array */
    private $properties;

    public function __construct(array $values)
    {
        $this->name = (string) $values['name'];
        $this->programmingLanguage = (string) $values['programmingLanguage'];
        $this->properties = (new Serializer())->decode($values['properties']);
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
