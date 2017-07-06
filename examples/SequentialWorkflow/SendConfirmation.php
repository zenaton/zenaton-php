<?php

use Zenaton\Common\Interfaces\TaskInterface;

class SendConfirmation implements TaskInterface
{
    protected $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function handle()
    {
        echo 'Sending notification to customer '.PHP_EOL;
        echo 'Customer ID: '. $this->booking->customer_id .PHP_EOL;
        echo 'Request ID: '. $this->booking->request_id .PHP_EOL;

        if ($this->booking->reserve_air) {
            echo 'Ticket ID: '. $this->booking->ticket_id .PHP_EOL;
        }

        if ($this->booking->reserve_car) {
            echo 'Car ID: '. $this->booking->car_id .PHP_EOL;
        }
    }
}
