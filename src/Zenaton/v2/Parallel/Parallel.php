<?php

namespace Zenaton\v2\Parallel;

use Zenaton\Traits\Zenatonable;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Exceptions\InvalidArgumentException;
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
