<?php

namespace DTL\Extension\Fink\Model;

use Countable;
use DTL\Extension\Fink\Model\Url;

interface UrlQueue extends Countable
{
    public function enqueue(Url $url): void;

    public function dequeue(): ?Url;
}
