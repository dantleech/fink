<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Dispatcher;
use DTL\Extension\Fink\Model\Report;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\FormatterHelper;

class UrlListDisplay implements Display
{
    public function render(OutputFormatterInterface $formatter, Dispatcher $dispatcher): string
    {
        $statuses = [];
        foreach ($dispatcher->store() as $index => $report) {
            $statusCode = $report->statusCode();
            $statuses[] = sprintf(
                $this->resolveFormat($index + 1 === count($dispatcher->store()), $report),
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
