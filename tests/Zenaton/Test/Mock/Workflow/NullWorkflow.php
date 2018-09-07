<?php

namespace Zenaton\Test\Mock\Workflow;

use Zenaton\Interfaces\WorkflowInterface;

/**
 * A workflow doing nothing.
 */
class NullWorkflow implements WorkflowInterface
{
    public function handle()
    {
    }
}
