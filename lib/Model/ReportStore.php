<?php

namespace DTL\Extension\Fink\Model;

use Countable;
use Iterator;
use DTL\Extension\Fink\Model\Report;
use IteratorAggregate;

interface ReportStore extends Countable, IteratorAggregate
{
    public function add(Report $report): void;
}
