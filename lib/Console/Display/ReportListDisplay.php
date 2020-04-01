<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\HttpStatusCode;
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
                    $statusCode ? $this->formatStatusCode($statusCode) : '---',
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

        if (null === $report->statusCode() || $report->statusCode()->isError()) {
            $style[] = 'bg=red;fg=white';
        }

        if (!$style) {
            return '%s';
        }

        return sprintf('<%s>%%s</>', implode(';', $style));
    }

    private function formatStatusCode(HttpStatusCode $statusCode): string
    {
        if ($statusCode->isError()) {
            return $statusCode->toString();
        }

        if ($statusCode->isRedirect()) {
            return '<comment>' . $statusCode->toString() . '</>';
        }

        if ($statusCode->isSuccess()) {
            return '<info>' . $statusCode->toString() . '</>';
        }

        return $statusCode->toString();
    }
}
