<?php

namespace DTL\Extension\Fink\Tests\Unit\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Console\Display\RateDisplay;
use DTL\Extension\Fink\Model\Status;
use Symfony\Component\Console\Helper\FormatterHelper;

class RateDisplayTest extends DisplayTestCase
{
    public function testRunningForZeroTime()
    {
        $display = $this->create();
        $output = $display->render($this->formatter, new Status());

        $this->assertEquals(<<<'EOT'
Running for 0.0 seconds, 0.00 requests per second
EOT
        , FormatterHelper::removeDecoration($this->formatter, $output));
    }

    public function testRunningForOneSecond()
    {
        $display = $this->create(2, microtime(true) - 1);
        $output = $display->render($this->formatter, new Status());

        $this->assertEquals(<<<'EOT'
Running for 1.0 seconds, 0.00 requests per second
EOT
        , FormatterHelper::removeDecoration($this->formatter, $output));
    }

    public function testRunningForOneSecondWithTwoRequests()
    {
        $status = new Status();
        $display = $this->create(2, microtime(true) - 1);
        $display->render($this->formatter, $status);
        $status->requestCount++;
        $display->render($this->formatter, $status);
        $output = $display->render($this->formatter, new Status());

        $this->assertEquals(<<<'EOT'
Running for 1.0 seconds, 1.00 requests per second
EOT
        , FormatterHelper::removeDecoration($this->formatter, $output));
    }

    public function testFiveSecondsWithTwoRequestsWindowSize4()
    {
        $status = new Status();
        $display = $this->create(4, microtime(true) - 5);

        $display->render($this->formatter, $status);
        $status->requestCount++;
        $display->render($this->formatter, $status);
        $output = $display->render($this->formatter, new Status());

        $this->assertEquals(<<<'EOT'
Running for 5.0 seconds, 0.25 requests per second
EOT
        , FormatterHelper::removeDecoration($this->formatter, $output));
    }

    private function create(int $windowSize = 2, float $startTime = null): Display
    {
        return new RateDisplay($windowSize, $startTime);
    }
}
