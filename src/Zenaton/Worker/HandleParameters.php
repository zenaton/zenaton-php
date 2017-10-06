<?php

namespace Zenaton\Worker;

use Zenaton\Common\Traits\IsImplementationOfTrait;
use Zenaton\Common\Interfaces\TaskInterface;
use Zenaton\Common\Interfaces\WorkflowInterface;


class HandleParameters
{
    use IsImplementationOfTrait;

    public function process($classes)
    {
        $tasks = [];
        $workflows = [];
        $classes = array_map('trim', explode(',', $classes));

        foreach ($classes as $class) {
            if ($this->isImplementationOf($class, WorkflowInterface::class)) {
                $workflows[] = $class;
            }
            if ($this->isImplementationOf($class, TaskInterface::class)) {
                $tasks[] = $class;
            }
        }

        return [
            'tasks' => $tasks,
            'workflows' => $workflows
        ];
    }

}
