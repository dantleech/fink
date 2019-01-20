<?php

namespace DTL\Extension\Fink\Model\Queue;

use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlQueue;

class DedupeQueue implements UrlQueue
{
    /**
     * @var UrlQueue
     */
    private $innerQueue;

    private $seen = [];

    public function __construct(UrlQueue $innerQueue)
    {
        $this->innerQueue = $innerQueue;
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return $this->innerQueue->count();
    }

    public function enqueue(Url $url): void
    {
        if (isset($this->seen[$url->__toString()])) {
            return;
        }

        $this->seen[$url->__toString()] = true;

        $this->innerQueue->enqueue($url);
    }

    public function dequeue(): ?Url
    {
        return $this->innerQueue->dequeue();
    }
}
