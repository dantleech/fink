<?php

namespace DTL\Extension\Fink\Model\Publisher;

use Amp\ByteStream\OutputStream;
use DTL\Extension\Fink\Model\Publisher;
use DTL\Extension\Fink\Model\Report;
use DTL\Extension\Fink\Model\Serializer;

class StreamPublisher implements Publisher
{
    /**
     * @var OutputStream
     */
    private $outputStream;

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(OutputStream $outputStream, Serializer $serializer)
    {
        $this->outputStream = $outputStream;
        $this->serializer = $serializer;
    }

    public function publish(Report $report): void
    {
        \Amp\asyncCall(function (Report $report) {
            yield $this->outputStream->write(
                $this->serializer->serialize($report).PHP_EOL
            );
        }, $report);
    }
}
