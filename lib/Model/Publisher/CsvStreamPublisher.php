<?php

namespace DTL\Extension\Fink\Model\Publisher;

use DTL\Extension\Fink\Model\Publisher;
use DTL\Extension\Fink\Model\Report;
use RuntimeException;

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
     * @var bool
     */
    private $stream;

    public function __construct(string $path, bool $withHeaders)
    {
        $stream = fopen($path, 'w');
        if (false === $stream) {
            throw new RuntimeException(sprintf(
                'Could not open stream for writing at path "%s"',
                $path
            ));
        }

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
