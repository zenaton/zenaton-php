<?php

namespace Zenaton\Worker;

use Zenaton\Common\Exceptions\ExternalZenatonException;
use Zenaton\Common\Interfaces\BoxInterface;
use Zenaton\Common\Interfaces\TaskInterface;
use Zenaton\Common\Interfaces\WorkflowInterface;
use Zenaton\Common\Traits\SingletonTrait;

class Helpers
{
    use SingletonTrait;

    public function execute()
    {
        $boxes = func_get_args();

        return $this->doExecute($boxes, true);
    }

    public function executeAsync()
    {
        $boxes = func_get_args();

        $this->doExecute($boxes, false);
    }

    protected function doExecute($boxes, $isSync)
    {
        $outputs = [];

        // check arguments'type
        $this->checkArgumentsType($boxes);

        // execution without zenaton
        foreach ($boxes as $box) {
            $outputs[] = $box->handle();
        }
        if ($isSync) {
            return (count($boxes) > 1) ? $outputs : $outputs[0];
        }

        return;
    }

    protected function checkArgumentsType($boxes)
    {
        $error = new ExternalZenatonException('arguments MUST be one or many objects implementing '.TaskInterface::class.
            ' or '.WorkflowInterface::class);

        // check there is at least one argument
        if (count($boxes) == 0) {
            throw $error;
        }

        // check each arguments'type
        $check = function ($arg) {
            if ( ! is_object($arg) || ! $arg instanceof BoxInterface) {
                throw $error;
            }
        };
        array_map($check, $boxes);
    }
}
