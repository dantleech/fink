<?php

namespace DTL\Extension\Fink\Model\Publisher;

use Amp\ByteStream\OutputStream;
use DTL\Extension\Fink\Model\Publisher;
use DTL\Extension\Fink\Model\Report;

class JsonStreamPublisher implements Publisher
{
    /**
     * @var OutputStream
     */
    private $outputStream;

    public function __construct(OutputStream $outputStream)
    {
        $this->outputStream = $outputStream;
    }

    public function publish(Report $report): void
    {
        \Amp\asyncCall(function (Report $report) {
            yield $this->outputStream->write(json_encode($report->toArray()).PHP_EOL);
        }, $report);
    }
}
