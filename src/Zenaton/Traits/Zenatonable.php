<?php

namespace Zenaton\Traits;

use Zenaton\Helpers;
use Zenaton\Engine\Engine;
use Zenaton\Query\Builder as QueryBuilder;

trait Zenatonable
{
    public function dispatch()
    {
        return Engine::getInstance()->dispatch([$this]);
    }

    public function execute()
    {
        return Engine::getInstance()->execute([$this]);
    }

    public static function whereId($id)
    {
        return (new QueryBuilder(get_called_class()))->whereId($id);
    }
}
