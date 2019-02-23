<?php

namespace DTL\Extension\Fink\Model\Limiter;

use DTL\Extension\Fink\Model\Limiter;
use DTL\Extension\Fink\Model\Status;

class ChainLimiter implements Limiter
{
    /**
     * @var Limiter[]
     */
    private $limiters = [];

    public function __construct(array $limiters)
    {
        foreach ($limiters as $limiter) {
            $this->add($limiter);
        }
    }

    public function limitReached(Status $status): bool
    {
        foreach ($this->limiters as $limiter) {
            if ($limiter->limitReached($status)) {
                return true;
            }
        }

        return false;
    }

    private function add(Limiter $limiter)
    {
        $this->limiters[] = $limiter;
    }
}
