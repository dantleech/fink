<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Dispatcher;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class RateDisplay implements Display
{
    private $lastTime;

    public function render(OutputFormatterInterface $output, Dispatcher $dispatcher): string
    {
    }
}
