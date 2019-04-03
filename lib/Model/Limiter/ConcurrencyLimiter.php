<?php

namespace DTL\Extension\Fink\Model\Limiter;

use DTL\Extension\Fink\Model\Limiter;
use DTL\Extension\Fink\Model\Status;

class ConcurrencyLimiter implements Limiter
{
    /**
     * @var int
     */
    private $maxConcurrency;

    public function __construct(int $maxConcurrency)
    {
        $this->maxConcurrency = $maxConcurrency;
    }

    public function limitReached(Status $status): bool
    {
        return $status->nbConcurrentRequests >= $this->maxConcurrency;
    }
}
