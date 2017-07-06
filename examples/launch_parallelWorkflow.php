<?php

require_once __DIR__.'/autoload.php';

use Zenaton\Client\Client;

$client = new Client($app_id, $api_token, $app_env);

$item = (object)[
    'name' => 'shirt',
];


$instance = $client->start(new ParallelWorkflow($item));
$id = $instance->getId();
echo 'launched! '. $id.PHP_EOL;
