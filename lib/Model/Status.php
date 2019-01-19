<?php

namespace DTL\Extension\Fink\Model;

class Status
{
    /**
     * @var int
     */
    public $concurrentRequests;

    /**
     * @var int
     */
    public $requestCount;

    /**
     * @var string
     */
    public $lastUrl;

    public function __construct(int $concurrentRequests = 0, int $requestCount = 0)
    {
        $this->concurrentRequests = $concurrentRequests;
        $this->requestCount = $requestCount;
    }
}
