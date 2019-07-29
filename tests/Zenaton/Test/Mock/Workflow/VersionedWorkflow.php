<?php

namespace Zenaton\Test\Mock\Workflow;

use Zenaton\Workflows\Version;

class VersionedWorkflow extends Version
{
    public function versions()
    {
        return [
            VersionedWorkflow_v0::class,
        ];
    }
}
