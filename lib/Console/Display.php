<?php

namespace DTL\Extension\Fink\Console;

use DTL\Extension\Fink\Model\Status;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

interface Display
{
    public function render(OutputFormatterInterface $output, Status $status): string;
}
