<?php

use Zenaton\Parallel\Parallel;

if ( ! function_exists('parallel')) {
    function parallel()
    {
        return new Parallel(func_get_args());
    }
}
