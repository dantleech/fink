<?php

namespace DTL\Extension\Fink\Model\Store;

use ArrayIterator;
use DTL\Extension\Fink\Model\Report;
use DTL\Extension\Fink\Model\ReportStore;
use Iterator;

final class CircularReportStore implements ReportStore
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

    public function add(Report $report): void
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
