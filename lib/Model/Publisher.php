<?php

namespace DTL\Extension\Fink\Model;

interface Publisher
{
    public function publish(Report $report): void;
}
