<?php

namespace DTL\Extension\Fink\Model\Queue;

use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlQueue;

class FirstExternalOnlyQueue implements UrlQueue
{
    /**
     * @var UrlQueue
     */
    private $innerQueue;

    /**
     * @var Url
     */
    private $baseUrl;

    public function __construct(UrlQueue $innerQueue, Url $baseUrl)
    {
        $this->innerQueue = $innerQueue;
        $this->baseUrl = $baseUrl;
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
        if (!$referringUrl = $url->referrer()) {
            // this is the "root" URL... nothing to do here
            $this->innerQueue->enqueue($url);
            return;
        }

        if (false === $referringUrl->equalsOrDescendantOf($this->baseUrl)) {
            return;
        }

        $this->innerQueue->enqueue($url);
    }

    public function dequeue(): ?Url
    {
        return $this->innerQueue->dequeue();
    }
}
