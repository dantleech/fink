<?php

namespace DTL\Extension\Fink\Model;

class Status
{
    /**
     * @var int
     */
    public $nbConcurrentRequests = 0;

    /**
     * @var int
     */
    public $requestCount = 0;

    /**
     * @var string
     */
    public $lastUrl;

    /**
     * @var int
     */
    public $nbFailures = 0;

    /**
     * @var int
     */
    public $queueSize = 0;

    /**
     * @var ImmutableReportStore
     */
    public $reportStore;

    public function failurePercentage(): float
    {
        if ($this->nbFailures === 0) {
            return 0;
        }

        return $this->nbFailures / $this->requestCount * 100;
    }

    public function reportStore(): ImmutableReportStore
    {
        return $this->reportStore;
    }
}
