<?php

namespace DTL\Extension\Fink\Model;

interface Serializer
{
    public function serialize(Report $report): string;
}
