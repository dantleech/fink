<?php

namespace DTL\Extension\Fink\Model\Serializer;

use DTL\Extension\Fink\Model\Report;
use DTL\Extension\Fink\Model\Serializer;

class JsonSerializer implements Serializer
{
    public function serialize(Report $report): string
    {
        return json_encode($report->toArray());
    }
}
