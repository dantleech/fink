<?php

namespace DTL\Extension\Fink\Model\Queue;

use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlQueue;

class MaxDistanceQueue implements UrlQueue
{
    /**
     * @var UrlQueue
     */
    private $innerQueue;

    /**
     * @var int
     */
    private $maxDistance;

    public function __construct(UrlQueue $innerQueue, int $maxDistance)
    {
        $this->innerQueue = $innerQueue;
        $this->maxDistance = $maxDistance;
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
        if ($url->distanceIsGreaterThan($this->maxDistance)) {
            return;
        }

        $this->innerQueue->enqueue($url);
    }

    public function dequeue(): ?Url
    {
        return $this->innerQueue->dequeue();
    }
}
