<?php

namespace Zenaton\Parallel;

use Zenaton\Engine\Engine;

class Parallel
{
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new collection.
     *
     * @param mixed $items
     */
    public function __construct()
    {
        $this->items = func_get_args();
    }

    public function dispatch()
    {
        Engine::getInstance()->dispatch($this->items);
    }

    public function execute()
    {
        return Engine::getInstance()->execute($this->items);
    }
}
