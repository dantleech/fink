<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\Model\Store\ImmutableReportStore;
use DTL\Extension\Fink\Model\Store\NullReportStore;

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
     * @var ImmutableReportStore<Report>
     */
    private $reportStore;

    public function __construct(ImmutableReportStore $reportStore = null)
    {
        $this->reportStore = $reportStore ?: new ImmutableReportStore(new NullReportStore());
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
