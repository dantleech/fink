<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Report;
use DTL\Extension\Fink\Model\Status;
use DTL\Extension\Fink\Model\Store\ImmutableReportStore;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class RateDisplay implements Display
{
    private $lastRequestCount = 0;
    private $requestCounts = [];
    private $initialTime;

    /**
     * @var int
     */
    private $windowSize;

    public function __construct(int $windowSize = 10, ?float $initialTime = 0)
    {
        $this->initialTime = $initialTime ?: microtime(true);
        $this->windowSize = $windowSize;
    }

    public function render(OutputFormatterInterface $output, Status $status): string
    {
        $nbNewRequests = $status->requestCount - $this->lastRequestCount;
        $this->lastRequestCount = $status->requestCount;

        if ($nbNewRequests >= 1) {
            $this->requestCounts[$this->microseconds()] = $nbNewRequests;
        }

        $ratePerSecond = $this->ratePerSecond();
        $averageRequestTime = $this->averageRequestTime($status->reportStore());

        return sprintf(
            '<info>Rate</>: %s <info>r/sec</>, %s<info> ms/r</>',
            number_format($ratePerSecond, 2),
            number_format($averageRequestTime * 0.001, 2)
        );
    }

    private function microseconds(): int
    {
        return (int) (microtime(true) * 1E6);
    }

    private function windowSize(): int
    {
        return (int) ($this->windowSize * 1E6);
    }

    private function requestCountInWindow(float $threshold): int
    {
        $requests = 0;
        foreach ($this->requestCounts as $time => $count) {
            if ($time < $threshold) {
                unset($this->requestCounts[$time]);
                continue;
            }
            $requests += $count;
        }

        return (int) $requests;
    }

    private function ratePerSecond(): float
    {
        $dropThreshold = $this->microseconds() - $this->windowSize();
        $requests = $this->requestCountInWindow($dropThreshold);
        $elapsed = $this->microseconds() - $dropThreshold;
        
        if ($this->microseconds() - ($this->initialTime * 1E6) < $this->windowSize()) {
            $elapsed = $this->microseconds() - ($this->initialTime * 1E6);
        }

        return ($requests / $elapsed) * 1E6;
    }

    private function averageRequestTime(ImmutableReportStore $reportStore)
    {
        if (count($reportStore) === 0) {
            return 0;
        }
        $totalRequestTime = array_reduce(
            iterator_to_array($reportStore),
            function (int $total, Report $report) {
                return $total += $report->requestTime();
            },
            0
        );

        return $totalRequestTime / count($reportStore);
    }
}
