<?php

namespace DTL\Extension\Fink\Model;

interface ReportStore extends ImmutableReportStore
{
    public function add(Report $report): void;
}
