<?php

namespace DTL\Extension\Fink\Model\Queue;

use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlQueue;

class ExcludingQueue implements UrlQueue
{
    /**
     * @var UrlQueue
     */
    private $innerQueue;

    /**
     * @var array
     */
    private $patterns;

    public function __construct(UrlQueue $innerQueue, array $patterns)
    {
        $this->innerQueue = $innerQueue;
        $this->patterns = $patterns;
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
        foreach ($this->patterns as $pattern) {
            if (preg_match('{' . $pattern . '}', $url->__toString())) {
                return;
            }
        }

        $this->innerQueue->enqueue($url);
    }

    public function dequeue(): ?Url
    {
        return $this->innerQueue->dequeue();
    }
}
