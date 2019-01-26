<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\DispatcherBuilder;

class DispatcherBuilderFactory
{
    public function createForUrl(string $url): DispatcherBuilder
    {
        return DispatcherBuilder::create($url);
    }
}
