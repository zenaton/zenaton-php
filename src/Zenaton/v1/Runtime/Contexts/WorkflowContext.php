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
     * @var null|string
     */
    public $id;

    public function __construct(array $values = [])
    {
        $this->id = isset($values['id']) ? (string) $values['id'] : null;
    }
}
