<?php

use Zenaton\Worker\HandleParameters;


$classes = $argv[1];
$autoload = $argv[2];

// autoload
require $autoload;

$response = ((new HandleParameters())->process($classes));
echo json_encode($response);
