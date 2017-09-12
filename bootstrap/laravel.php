<?php

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
| (from vendor/zenaton/zenaton-php/bootstrap)
*/
require __DIR__.'/../../../../bootstrap/autoload.php';

/*
|--------------------------------------------------------------------------
| Load the framework
|--------------------------------------------------------------------------
| (from vendor/zenaton/zenaton-php/bootstrap)
*/

$app = require_once __DIR__.'/../../../../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Boot the application
|--------------------------------------------------------------------------
*/

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
