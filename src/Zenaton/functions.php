<?php

use Zenaton\Worker\Helpers;

if ( ! function_exists('execute')) {
    function execute()
    {
        return call_user_func_array([Helpers::getInstance(), 'execute'], func_get_args());
    }
}

if ( ! function_exists('executeAsync')) {
    function executeAsync()
    {
        return call_user_func_array([Helpers::getInstance(), 'executeAsync'], func_get_args());
    }
}
