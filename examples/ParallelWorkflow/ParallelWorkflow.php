<?php

use Zenaton\Common\Interfaces\WorkflowInterface;

class ParallelWorkflow implements WorkflowInterface
{
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function handle()
    {
        list($priceA, $priceB) = execute(
            new GetPriceFromProviderA($this->item),
            new GetPriceFromProviderB($this->item)
        );

        if ($priceA < $priceB) {
            execute(new OrderFromProviderA($this->item));
        } else {
            execute(new OrderFromProviderB($this->item));
        }
    }
}
