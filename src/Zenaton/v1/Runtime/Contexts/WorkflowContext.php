<?php

namespace Zenaton\Runtime\Contexts;

/**
 * Represents the current runtime context of a workflow.
 */
final class WorkflowContext
{
    /**
     * The workflow identifier.
     *
     * @var string
     */
    private $id;

    public function __construct(array $values = [])
    {
        $this->id = (string) $values['id'];
    }

    /**
     * Returns the workflow identifier.
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }
}
