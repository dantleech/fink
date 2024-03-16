<?php

namespace DTL\Extension\Fink\Model\Store;

use ArrayIterator;
use DTL\Extension\Fink\Model\Report;
use DTL\Extension\Fink\Model\ReportStore;
use Traversable;

class NullReportStore implements ReportStore
{
    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * @return Traversable<int,Report>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator();
    }

    public function add(Report $report): void
    {
    }
}
