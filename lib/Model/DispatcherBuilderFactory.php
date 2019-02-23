<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\DispatcherBuilder;

class DispatcherBuilderFactory
{
    public function createForUrls($urls): DispatcherBuilder
    {
        return DispatcherBuilder::create($urls);
    }
}
