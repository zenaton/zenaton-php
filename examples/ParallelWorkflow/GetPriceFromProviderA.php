<?php

use Zenaton\Common\Interfaces\TaskInterface;

class GetPriceFromProviderA implements TaskInterface
{
    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function handle()
    {
        // Fake API request to get price from provider A
        echo "Contacting provider A to get the price..";
        sleep(rand(5,10));
        $price = rand(80, 100);
        echo 'Price from Provider A is: '. $price. PHP_EOL;
        return $price;
    }
}
