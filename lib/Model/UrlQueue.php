<?php

namespace DTL\Extension\Fink\Model;

use Countable;
use DTL\Extension\Fink\Model\Exception\UrlQueueEmpty;

final class UrlQueue implements Countable
{
    private $urls = [];

    public function enqueue(Url $url)
    {
        $this->urls[] = $url;
    }

    public function dequeue(): Url
    {
        if (empty($this->urls)) {
            throw new UrlQueueEmpty('URL queue is empty');
        }

        return \array_shift($this->urls);
    }

    public function isEmpty(): bool
    {
        return 0 === count($this->urls);
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return count($this->urls);
    }
}
