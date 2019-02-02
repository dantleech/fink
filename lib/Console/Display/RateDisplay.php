<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Status;
use DateTimeImmutable;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\FormatterHelper;

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

        $dropThreshold = $this->microseconds() - $this->windowSize();
        $requests = $this->requestCountInWindow($dropThreshold);
        $elapsed = $this->microseconds() - $dropThreshold;

        if ($this->microseconds() - ($this->initialTime * 1E6) < $this->windowSize()) {
            $elapsed = $this->microseconds() - ($this->initialTime * 1E6);
        }
        $perSecond = ($requests / $elapsed) * 1E6;

        return sprintf(
            '<info>Running for </>%s <info>seconds, </>%s <info>requests per second</>',
            number_format(microtime(true) - $this->initialTime, 1),
            number_format($perSecond, 2)
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
}
