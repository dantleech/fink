<?php

namespace DTL\Extension\Fink\Model\Limiter;

use DTL\Extension\Fink\Model\Limiter;
use DTL\Extension\Fink\Model\Limiter\Exception\InvalidRate;
use DTL\Extension\Fink\Model\Status;

class RateLimiter implements Limiter
{
    private $lastRequest = null;

    /**
     * @var float
     */
    private $interval;

    public function __construct(float $ratePerSecond)
    {
        if ($ratePerSecond == 0) {
            throw new InvalidRate(
                'Rate cannot be zero'
            );
        }

        if ($ratePerSecond < 0) {
            throw new InvalidRate(sprintf(
                'Rate cannot be negative, got "%s"',
                (string) $ratePerSecond
            ));
        }

        $this->interval = $this->calculateInterval($ratePerSecond);
    }

    public function limitReached(Status $status): bool
    {
        $seconds = microtime(true);

        if (null === $this->lastRequest) {
            $this->lastRequest = $seconds;
            return false;
        }

        if ($seconds >= ($this->lastRequest + $this->interval)) {
            $this->lastRequest = $seconds;
            return false;
        }

        return true;
    }

    private function calculateInterval(float $ratePerSecond): float
    {
        return 1 / $ratePerSecond;
    }
}
