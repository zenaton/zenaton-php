<?php

use Zenaton\Common\Exceptions\ExternalZenatonException;
use Zenaton\Worker\MicroServer;
use Zenaton\Worker\Slave;

// just in case autoload file has been moved
if (!file_exists($argv[1])) {
    $e = new ExternalZenatonException('Can not launch new slave - autoload file '.$argv[1].' not found');
    throw $e;
}

// autoload
require $argv[1];

// define shutdown to catch non-thrown error
function shutdown()
{
    $ms = MicroServer::getInstance();

    $last = error_get_last();
    $str = '"'.$last['message'].'" on line '.$last['line'].' in file "'.$last['file'].'"';
    if ($ms->isWorking()) {
        $e = new ExternalZenatonException($str);
        $ms->failWorker($e);
    }

    if ($ms->isDeciding()) {
        $e = new ExternalZenatonException($str);
        $ms->failDecider($e);
    }
}
register_shutdown_function('shutdown');

// launch script
// arg 1 : autoload, arg2: instance_id, arg3: slave_id
(new Slave($argv[2], $argv[3]))->process();
