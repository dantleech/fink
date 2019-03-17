<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Status;
use DateInterval;
use DateTimeImmutable;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class UptimeDisplay implements Display
{
    /**
     * @var DateTimeImmutable
     */
    private $initialTime;

    /**
     * @var int
     */
    private $windowSize;

    public function __construct(?DateTimeImmutable $initialTime = null)
    {
        $this->initialTime = $initialTime ?: new DateTimeImmutable();
    }

    public function render(OutputFormatterInterface $output, Status $status): string
    {
        $interval = $this->initialTime->diff(new DateTimeImmutable());

        return sprintf(
            '<info>Uptime:</> %s',
            $this->formatDuration($interval)
        );
    }

    private function formatDuration(DateInterval $interval): string
    {
        return sprintf(
            '%02dh %02dm %02ds',
            $interval->h,
            $interval->i,
            $interval->s
        );
    }
}
