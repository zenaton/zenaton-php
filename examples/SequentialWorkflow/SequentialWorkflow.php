<?php

use Zenaton\Common\Interfaces\WorkflowInterface;

class SequentialWorkflow implements WorkflowInterface
{
    protected $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function handle()
    {
        if ($this->booking->reserve_air) {
            $booking = execute(new ReserveAir($this->booking));
        }

        if ($this->booking->reserve_car) {
            $booking = execute(new ReserveCar($booking));
        }

        execute(new SendConfirmation($booking));
    }
}
