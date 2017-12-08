<?php

namespace Zenaton\Parallel;

use Zenaton\Traits\Zenatonable;
use Zenaton\Interfaces\TaskInterface;
use Zenaton\Exceptions\InvalidArgumentException;
use Zenaton\Engine\Engine;

class Collection
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
        foreach ($items as $item) {
            if ((! is_object($item)) || (! $item instanceof TaskInterface)) {
                throw new InvalidArgumentException('Parallel element must be an object implementing '.TaskInterface::class);
            }
        }

        $this->items = $items;
    }

    public function dispatch()
    {
        return Engine::getInstance()->dispatch($this->items);
    }

    public function execute()
    {
        return Engine::getInstance()->execute($this->items);
    }
}
