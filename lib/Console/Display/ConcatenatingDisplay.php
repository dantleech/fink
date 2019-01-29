<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Status;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class ConcatenatingDisplay implements Display
{
    /**
     * @var Display[]
     */
    private $displays = [];

    public function __construct(array $displays)
    {
        $this->displays = array_map(function (Display $display) {
            return $display;
        }, $displays);
    }

    public function render(OutputFormatterInterface $formatter, Status $status): string
    {
        return implode(PHP_EOL, array_map(function (Display $display) use ($formatter, $status) {
            return $display->render($formatter, $status);
        }, $this->displays));
    }
}
