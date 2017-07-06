<?php

use Zenaton\Common\Interfaces\TaskInterface;

class OrderFromProviderA implements TaskInterface
{
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function handle()
    {
        // Fake API request to order from provider A
        echo 'Order "'. $this->item->name.'" from Provider A'.PHP_EOL;
    }
}
