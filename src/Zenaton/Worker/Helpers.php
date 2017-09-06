<?php

namespace Zenaton\Worker;

use Exception;
use Zenaton\Worker\OutputBox;
use Zenaton\Common\Exceptions\ExternalZenatonException;
use Zenaton\Common\Exceptions\InternalZenatonException;
use Zenaton\Common\Exceptions\ScheduledBoxException;
use Zenaton\Common\Exceptions\ModifiedDeciderException;
use Zenaton\Common\Interfaces\BoxInterface;
use Zenaton\Common\Interfaces\TaskInterface;
use Zenaton\Common\Interfaces\WorkflowInterface;
use Zenaton\Common\Traits\SingletonTrait;
use Zenaton\Common\Services\Jsonizer;

class Helpers
{
    use SingletonTrait;

    protected $microserver;
    protected $flow;

    public function __construct()
    {
        $this->microserver = MicroServer::getInstance();
        $this->flow = Workflow::getInstance();
        $this->jsonizer = new Jsonizer();
    }

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
        if (!$this->isExecutedWithZenaton()) {
            foreach ($boxes as $box) {
                $outputs[] = $box->handle();
            }
            if ($isSync) {
                return (count($boxes) > 1) ? $outputs : $outputs[0];
            }

            return;
        }

        // construct array of decorated boxes
        $dboxes = [];
        foreach ($boxes as $box) {
            // Go to the next position
            if (! $isSync) {
                $this->flow->nextAsync();
            } elseif (count($boxes) > 1) {
                $this->flow->nextParallel();
            } else {
                $this->flow->next();
            }
            //
            $dboxes[] = (new OutputBox($box))->setPosition($this->flow->getPosition());
        }

        // schedule task or get result if already done
        $response = $this->microserver->execute($dboxes);

        // Decider was modified
        if ($response->status === 'modified') {
            throw new ModifiedDeciderException;
        }

        // Nothing more to do for asynchronous execution
        if (! $isSync) {
            return;
        }

        if ($response->status === 'scheduled') {
            throw new ScheduledBoxException;
        }

        if ($response->status === 'completed') {
            // Set properties for last execution
            $this->flow->setProperties($response->properties);


            // return outputs
            $outputs = $response->outputs;
            // var_dump($outputs);

            return (count($outputs) > 1) ? $outputs : $outputs[0];
        }

        throw new InternalZenatonException('InputBox with Unkwnon status at position '.$this->flow->getPosition());

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

    public function isExecutedWithZenaton()
    {
        return !is_null($this->microserver->getUuid());
    }
}
