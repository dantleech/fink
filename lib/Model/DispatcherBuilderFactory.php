<?php

namespace DTL\Extension\Fink\Model;

class DispatcherBuilderFactory
{
    public function createForUrl(string $url): DispatcherBuilder
    {
        return DispatcherBuilder::create($url);
    }
}
