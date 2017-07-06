<?php

use Zenaton\Common\Interfaces\TaskInterface;

class OrderFromProviderB implements TaskInterface
{
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function handle()
    {
        // Fake API request to order from provider B

        echo 'Order '. $this->item->name.' from Provider B'.PHP_EOL;
    }
}
