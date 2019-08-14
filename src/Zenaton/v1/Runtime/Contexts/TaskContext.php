<?php

namespace Zenaton\Runtime\Contexts;

/**
 * Represents the current runtime context of a class.
 *
 * The information provided by the context can be useful to alter the behaviour of the task.
 * For example, you can use the attempt index to know if a task has been automatically retried or not and how many times,
 * and decide to do something when you did not expect the task to be retried more than X times.
 * You can also use the attempt number in the `onErrorRetryDelay` method of a task in order to implement complex
 * retry strategies.
 */
final class TaskContext
{
    /**
     * The current attempt index.
     *
     * When the task is executed for the first time, the value will be `1`.
     * Every time the task is automatically retried, the value is increased.
     * When a manual retry is instructed, the value goes back to `1`.
     *
     * @var null|int
     */
    public $attemptIndex;

    /**
     * The task identifier.
     *
     * @var null|string
     */
    public $id;

    public function __construct(array $values = [])
    {
        $this->attemptIndex = isset($values['attempt_index']) ? (int) $values['attempt_index'] : null;
        $this->id = isset($values['id']) ? (string) $values['id'] : null;
    }
}
