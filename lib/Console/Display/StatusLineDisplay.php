<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Dispatcher;
use DTL\Extension\Fink\Model\Report;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Helper\FormatterHelper;

class StatusLineDisplay implements Display
{
    public function render(OutputFormatterInterface $formatter, Dispatcher $dispatcher): string
    {
        $status = $dispatcher->status();

        $line = sprintf(
            '<comment>CON</>: %s <comment>QUE</>: %s <comment>NOK</>: %s/%s (%s%%)',
            $status->nbConcurrentRequests,
            $status->queueSize,
            $status->nbFailures,
            $status->requestCount,
            number_format($dispatcher->status()->failurePercentage(), 2)
        );

        return implode(PHP_EOL, [
            str_repeat('-', FormatterHelper::strlenWithoutDecoration($formatter, $line)),
            $line
        ]);
    }
}
