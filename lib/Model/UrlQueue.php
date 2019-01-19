<?php

namespace DTL\Extension\Fink\Model;

use Countable;

interface UrlQueue extends Countable
{
    public function enqueue(Url $url): void;

    public function dequeue(): ?Url;
}
