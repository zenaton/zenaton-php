<?php

use Zenaton\Common\Interfaces\TaskInterface;

class ReserveAir implements TaskInterface
{
    protected $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function handle()
    {
        echo 'Reserving airline for Request ID: '. $this->booking->request_id .PHP_EOL;
        $this->booking->ticket_id = '154782684269';
        return $this->booking;
    }
}
