<?php

namespace DTL\Extension\Fink\Console;

use DTL\Extension\Fink\Model\Dispatcher;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

interface Display
{
    public function render(OutputFormatterInterface $output, Dispatcher $dispatcher): string;
}
