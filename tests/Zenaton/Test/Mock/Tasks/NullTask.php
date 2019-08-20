<?php

namespace Zenaton\Test\Mock\Tasks;

use Zenaton\Interfaces\TaskInterface;

/**
 * A task doing nothing.
 */
class NullTask implements TaskInterface
{
    public function handle()
    {
    }
}
