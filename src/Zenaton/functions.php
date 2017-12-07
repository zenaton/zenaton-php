<?php

use Zenaton\Parallel\Collection;

if ( ! function_exists('parallel')) {
    function parallel()
    {
        return new Collection(func_get_args());
    }
}
