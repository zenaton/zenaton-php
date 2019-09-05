<?php

namespace Zenaton\Traits;

use Zenaton\Engine\Engine;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Interfaces\WorkflowInterface;
use Zenaton\Query\Builder as QueryBuilder;
use Zenaton\Runtime\Contexts\TaskContext;
use Zenaton\Runtime\Contexts\WorkflowContext;

trait Zenatonable
{
    /** @var TaskContext|WorkflowContext */
    private $context;

    /**
     * Sets the  runtime context of a task or workflow.
     *
     * @param TaskContext|WorkflowContext $context
     *
     * @throws \LogicException if the context is already set and the method is called again
     *
     * @since 0.4
     *
     * @internal Used by the Zenaton agent. Should not be called by user code.
     */
    public function setContext($context)
    {
        if (null !== $this->context) {
            throw new \LogicException('Context is already set and cannot be mutated.');
        }

        $this->context = $context;
    }

    /**
     * Returns the runtime context of a task or workflow.
     *
     * This method will return an empty context object if the context was not previously set by the agent.
     *
     * @return TaskContext|WorkflowContext
     *
     * @since 0.4
     */
    public function getContext()
    {
        if (null === $this->context) {
            if ($this instanceof WorkflowInterface) {
                return new WorkflowContext();
            }
            if ($this instanceof TaskInterface) {
                return new TaskContext();
            }

            throw new \LogicException(\sprintf('Can only return a context object for classes implementing %s or %s interfaces.', WorkflowInterface::class, TaskInterface::class));
        }

        return $this->context;
    }

    public function dispatch()
    {
        return Engine::getInstance()->dispatch([$this])[0];
    }

    public function execute()
    {
        return Engine::getInstance()->execute([$this])[0];
    }

    public function schedule($cron)
    {
        if (!\is_string($cron) || '' === $cron) {
            throw new \InvalidArgumentException('$cron parameter must be a non empty string.');
        }

        Engine::getInstance()->schedule([$this], $cron);
    }

    public static function whereId($id)
    {
        return (new QueryBuilder(get_called_class()))->whereId($id);
    }
}
