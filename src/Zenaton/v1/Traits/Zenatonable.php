<?php

namespace Zenaton\Traits;

use Zenaton\Engine\Engine;
use Zenaton\Query\Builder as QueryBuilder;

trait Zenatonable
{
    public function dispatch()
    {
        Engine::getInstance()->dispatch([$this]);
    }

    public function execute()
    {
        return Engine::getInstance()->execute([$this])[0];
    }

    public static function whereId($id)
    {
        return (new QueryBuilder(get_called_class()))->whereId($id);
    }
}
