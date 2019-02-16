<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Status;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class MemoryDisplay implements Display
{
    public function render(OutputFormatterInterface $output, Status $status): string
    {
        return sprintf(
            '<info>Memory</>: %s',
            number_format(memory_get_usage(true))
        );
    }
}
