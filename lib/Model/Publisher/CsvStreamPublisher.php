<?php

namespace DTL\Extension\Fink\Model\Publisher;

use DTL\Extension\Fink\Model\Publisher;
use DTL\Extension\Fink\Model\Report;

class CsvStreamPublisher implements Publisher
{
    /**
     * @var bool
     */
    private $withHeaders;

    /**
     * @var bool
     */
    private $firstIteration = true;

    /**
     * @var resource
     */
    private $stream;

    public function __construct($stream, bool $withHeaders)
    {
        $this->stream = $stream;
        $this->withHeaders = $withHeaders;
    }

    public function publish(Report $report): void
    {
        if ($this->withHeaders && $this->firstIteration) {
            fputcsv($this->stream, array_keys($report->toArray()));
        }

        fputcsv($this->stream, $report->toArray());
        $this->firstIteration = false;
    }

    public function __destruct()
    {
        fclose($this->stream);
    }
}
