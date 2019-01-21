<?php

namespace DTL\Extension\Fink\Model;

use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;

final class CircularReportStore implements Countable, IteratorAggregate
{
    /**
     * @var Report[]
     */
    private $reports = [];

    /**
     * @var int
     */
    private $size;

    public function __construct(int $size)
    {
        $this->size = $size;
    }

    public function add(Report $report)
    {
        if (count($this->reports) >= $this->size) {
            array_shift($this->reports);
        }

        $this->reports[] = $report;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return count($this->reports);
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->reports);
    }
}
