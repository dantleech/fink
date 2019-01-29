<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Report;
use DTL\Extension\Fink\Model\Status;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class ReportListDisplay implements Display
{
    public function render(OutputFormatterInterface $formatter, Status $status): string
    {
        $statuses = [];
        foreach ($status->reportStore() as $index => $report) {
            $statusCode = $report->statusCode();
            $statuses[] = sprintf(
                $this->resolveFormat($index + 1 === count($status->reportStore()), $report),
                sprintf(
                    '[%3s] %s',
                    $statusCode ? $statusCode->toInt() : '---',
                    $report->url()->__toString()
                )
            );
        }

        return implode(PHP_EOL, $statuses);
    }

    private function resolveFormat(bool $last, Report $report)
    {
        $style = [];
        if ($last) {
            $style[] = 'options=bold';
        }

        if (!$report->isSuccess()) {
            $style[] = 'bg=red;fg=white';
        }

        if (!$style) {
            return '%s';
        }

        return sprintf('<%s>%%s</>', implode(';', $style));
    }
}
