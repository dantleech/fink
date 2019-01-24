<?php

namespace DTL\Extension\Fink\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Model\Dispatcher;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class ConcatenatingDisplay implements Display
{
    /**
     * @var Display[]
     */
    private $displays = [];

    public function __construct(array $displays)
    {
        $this->displays = array_map(function(Display $display) {
            return $display;
        }, $displays);
    }

    public function render(OutputFormatterInterface $formatter, Dispatcher $dispatcher): string
    {
        return implode(PHP_EOL, array_map(function (Display $display) use ($formatter, $dispatcher) {
            return $display->render($formatter, $dispatcher);
        }, $this->displays));
    }
}
