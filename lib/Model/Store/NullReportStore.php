<?php

namespace DTL\Extension\Fink\Model\Store;

use ArrayIterator;
use DTL\Extension\Fink\Model\Report;
use DTL\Extension\Fink\Model\ReportStore;

class NullReportStore implements ReportStore
{
    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new ArrayIterator();
    }

    public function add(Report $report): void
    {
    }
}
