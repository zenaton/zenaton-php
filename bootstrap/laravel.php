<?php

// (from vendor/zenaton/zenaton-php/bootstrap)


/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
$autoload = __DIR__.'/../../../../bootstrap/autoload.php';
if (! file_exists($autoload)) {
    $autoload = __DIR__.'/../../../../vendor/autoload.php';
}
require $autoload;

/*
|--------------------------------------------------------------------------
| Load the framework
|--------------------------------------------------------------------------
| (from vendor/zenaton/zenaton-php/bootstrap)
*/

// laravel 5.*
$bootstrap = __DIR__.'/../../../../bootstrap/app.php';
if (file_exists($bootstrap)) {
    $app = require_once $bootstrap;
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    return;
} 

// laravel 4.*
$bootstrap = __DIR__.'/../../../../bootstrap/start.php';
if (file_exists($bootstrap)) {
    $app = require_once $bootstrap;
    $app->boot();
    return;
}
