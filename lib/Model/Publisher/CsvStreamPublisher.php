<?php

namespace DTL\Extension\Fink\Model\Publisher;

use DTL\Extension\Fink\Model\Publisher;
use DTL\Extension\Fink\Model\Report;
use DTL\Extension\Fink\Model\Serializer;
use League\Csv\Writer;
use RuntimeException;

class CsvStreamPublisher implements Publisher
{
    /**
     * @var resource
     */
    private $stream;

    public function __construct(string $path)
    {
        $stream = fopen($path, 'w');
        if (false === $stream) {
            throw new RuntimeException(sprintf(
                'Could not open stream for writing at path "%s"', $path
            ));
        }

        $this->stream = $stream;
    }

    public function publish(Report $report): void
    {
        fputcsv($this->stream, $report->toArray());
    }

    public function __destruct()
    {
        fclose($this->stream);
    }
}
