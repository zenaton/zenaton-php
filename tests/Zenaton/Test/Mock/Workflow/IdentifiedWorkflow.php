<?php

namespace Zenaton\Test\Mock\Workflow;

use Zenaton\Interfaces\WorkflowInterface;

/**
 * A workflow doing nothing and having a fixed identifier.
 */
class IdentifiedWorkflow implements WorkflowInterface
{
    public function handle()
    {
    }

    public function getId()
    {
        return 'static-identifier';
    }
}
