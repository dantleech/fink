<?php

namespace DTL\Extension\Fink\Model\Queue;

use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlQueue;

class ExternalDistanceLimitingQueue implements UrlQueue
{
    /**
     * @var UrlQueue
     */
    private $innerQueue;

    /**
     * @var Url
     */
    private $baseUrl;

    /**
     * @var int
     */
    private $maxDistance;

    public function __construct(UrlQueue $innerQueue, Url $baseUrl, int $maxDistance = 0)
    {
        $this->innerQueue = $innerQueue;
        $this->baseUrl = $baseUrl;
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
        if ($url->externalDistanceFrom($this->baseUrl) > $this->maxDistance) {
            return;
        }

        $this->innerQueue->enqueue($url);
    }

    public function dequeue(): ?Url
    {
        return $this->innerQueue->dequeue();
    }
}
