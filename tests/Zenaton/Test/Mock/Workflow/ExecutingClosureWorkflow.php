<?php

namespace Zenaton\Test\Mock\Workflow;

use Zenaton\Interfaces\WorkflowInterface;

/**
 * A workflow running a given closure when its handle method is called.
 *
 * This class can be used when you need a workflow with a specific code in its handle
 * method without having to write a full class. You can write anythingyou want in the
 * closure, be it real processing or some PHPUnit assertions when testing.
 *
 * The given closure is bound to the workflow instance when executed.
 *
 * For an example of usage in testing, please refer to {@see VersionTest}.
 */
class ExecutingClosureWorkflow implements WorkflowInterface
{
    /**
     * @param \Closure $closure The closure to execute when the ::handle() method will be called
     */
    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    public function handle()
    {
        $this->closure->call($this);
    }
}
