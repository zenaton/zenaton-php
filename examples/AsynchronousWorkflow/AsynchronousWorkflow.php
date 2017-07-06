<?php

use Zenaton\Common\Interfaces\WorkflowInterface;

class AsynchronousWorkflow implements WorkflowInterface
{
    protected $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function handle()
    {
        $booking = execute(new ReserveAir($this->booking));

        $booking = execute(new ReserveCar($booking));

    }
}
