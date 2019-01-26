<?php

namespace DTL\Extension\Fink\Tests\Integration\Model\Publisher;

use DTL\Extension\Fink\Model\Publisher;
use DTL\Extension\Fink\Model\ReportBuilder;
use DTL\Extension\Fink\Model\Serializer;
use DTL\Extension\Fink\Model\Publisher\CsvStreamPublisher;
use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Tests\IntegrationTestCase;
use PHPUnit\Framework\TestCase;

class CsvStreamPublisherTest extends IntegrationTestCase
{
    const EXAMPLE_FILEANME = 'test';

    public function testPublishesToCsvFile()
    {
        $report = ReportBuilder::forUrl(Url::fromUrl('https://www.dantleech.com'))
            ->withStatus(200)
            ->build();

        $serialized = $this->create()->publish($report);
        $this->assertEquals(<<<'EOT'
https://www.dantleech.com,0,,200,0,

EOT
        , file_get_contents($this->workspace()->path(self::EXAMPLE_FILEANME)));
    }

    public function testPublishesToCsvFileWithHeaders()
    {
        $report = ReportBuilder::forUrl(Url::fromUrl('https://www.dantleech.com'))
            ->withStatus(200)
            ->build();

        $serialized = $this->create(true)->publish($report);
        $this->assertEquals(<<<'EOT'
https://www.dantleech.com,0,,200,0,

EOT
        , file_get_contents($this->workspace()->path(self::EXAMPLE_FILEANME)));
    }

    private function create(bool $withHeaders = false): Publisher
    {
        return new CsvStreamPublisher($this->workspace()->path(self::EXAMPLE_FILEANME), $withHeaders);
    }
}
