<?php

namespace Zenaton\Runtime\Contexts;

/**
 * Represents the current runtime context of a task.
 *
 * The information provided by the context can be useful to alter the behaviour of the task if required.
 * For example, you can use the retry index to know if a task has been automatically retried or not and how many times,
 * and decide to do something when you did not expect the task to be retried more than X times.
 * You can also use the retry index in the `onErrorRetryDelay` method of a task in order to implement complex
 * retry strategies.
 */
final class TaskContext
{
    /**
     * The current retry index.
     *
     * When the task is executed for the first time, the value will be `1`.
     * Every time the task is automatically retried, the value is increased by one.
     * When a manual retry is instructed, the value goes back to `1`.
     *
     * @var null|int
     */
    public $retryIndex;

    /**
     * The task identifier.
     *
     * @var null|string
     */
    public $id;

    public function __construct(array $values = [])
    {
        $this->retryIndex = isset($values['retry_index']) ? (int) $values['retry_index'] : null;
        $this->id = isset($values['id']) ? (string) $values['id'] : null;
    }
}
