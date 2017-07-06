<?php

require_once __DIR__.'/autoload.php';

use Zenaton\Client\Client;

$client = new Client($app_id, $api_token, $app_env);

$booking = (object)[
    'request_id' => '1234567890',
    'customer_id' => '1234567891',
    'reserve_car' => true,
    'reserve_air' => true
];


$instance = $client->start(new SequentialWorkflow($booking));
$id = $instance->getId();
echo 'launched! '. $id.PHP_EOL;
