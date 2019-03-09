<?php

namespace DTL\Extension\Fink\Tests\Integration\Model\Publisher;

use DTL\Extension\Fink\Model\Publisher;
use DTL\Extension\Fink\Model\ReportBuilder;
use DTL\Extension\Fink\Model\Publisher\CsvStreamPublisher;
use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Tests\IntegrationTestCase;

class CsvStreamPublisherTest extends IntegrationTestCase
{
    public const EXAMPLE_FILEANME = 'test';

    public function testPublishesToCsvFile()
    {
        $report = ReportBuilder::forUrl(Url::fromUrl('https://www.dantleech.com'))
            ->withStatus(200)
            ->build();

        $serialized = $this->create()->publish($report);
        $this->assertEquals(<<<'EOT'
0,,,,,0,200,https://www.dantleech.com

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
distance,exception,referrer,referrer_title,referrer_xpath,request_time,status,url
0,,,,,0,200,https://www.dantleech.com

EOT
        , file_get_contents($this->workspace()->path(self::EXAMPLE_FILEANME)));
    }

    private function create(bool $withHeaders = false): Publisher
    {
        return new CsvStreamPublisher($this->workspace()->path(self::EXAMPLE_FILEANME), $withHeaders);
    }
}
