<?php

use Zenaton\Common\Interfaces\TaskInterface;

class ReserveCar implements TaskInterface
{
    protected $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function handle()
    {
        echo 'Reserving car for Request ID: '. $this->booking->request_id .PHP_EOL;
        $this->booking->car_id = '154785236';
        return $this->booking;
    }
}
