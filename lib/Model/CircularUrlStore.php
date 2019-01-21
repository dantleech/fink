<?php

namespace DTL\Extension\Fink\Model;

use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;

final class CircularUrlStore implements Countable, IteratorAggregate
{
    /**
     * @var Url[]
     */
    private $urls = [];

    /**
     * @var int
     */
    private $size;

    public function __construct(int $size)
    {
        $this->size = $size;
    }

    public function add(Url $url)
    {
        if (count($this->urls) >= $this->size) {
            array_shift($this->urls);
        }

        $this->urls[] = $url;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->urls);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->urls);
    }
}
