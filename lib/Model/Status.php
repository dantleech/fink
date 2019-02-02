<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\Model\Store\ImmutableReportStore;

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
    private $reportStore;

    public function __construct(ImmutableReportStore $reportStore)
    {
        $this->reportStore = $reportStore;
    }

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
