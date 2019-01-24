<?php

namespace DTL\Extension\Fink\Console;

use DTL\Extension\Fink\Model\Dispatcher;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface Display
{
    public function render(OutputFormatterInterface $output, Dispatcher $dispatcher): string;
}
