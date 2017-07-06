<?php

require_once __DIR__.'/../../../autoload.php';

$classesDir = [
    __DIR__.'/SequentialWorkflow/',
    __DIR__.'/ParallelWorkflow/'
];

foreach ($classesDir as $directory) {
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($objects as $filename => $object) {
        if (preg_match('/\.php$/', $filename)) {
            include_once $filename;
        }
    }
}

require_once __DIR__.'/config.php';
