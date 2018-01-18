<?php

namespace Zenaton\v2\Parallel;

use Zenaton\Exceptions\InvalidArgumentException;

use Zenaton\v2\Traits\Zenatonable;
use Zenaton\v2\Interfaces\TaskInterface;
use Zenaton\v2\Engine\Engine;

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
     * @param  mixed  $items
     * @return void
     */
    public function __construct($items = [])
    {
        $this->items = $items;
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
