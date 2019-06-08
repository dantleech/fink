<?php

namespace DTL\Extension\Fink\Tests\Integration\Model\Publisher;

use DTL\Extension\Fink\Model\Publisher;
use DTL\Extension\Fink\Model\ReportBuilder;
use DTL\Extension\Fink\Model\Publisher\CsvStreamPublisher;
use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Tests\IntegrationTestCase;
use DateTimeImmutable;

class CsvStreamPublisherTest extends IntegrationTestCase
{
    public const EXAMPLE_FILEANME = 'test';

    public function testPublishesToCsvFile()
    {
        $report = ReportBuilder::forUrl(Url::fromUrl('https://www.dantleech.com'))
            ->withTimestamp(new DateTimeImmutable('2019-01-01 00:00:00 +00:00'))
            ->withStatus(200)
            ->build();

        $serialized = $this->create()->publish($report);
        $this->assertEquals(<<<'EOT'
0,,,,,0,200,https://www.dantleech.com,2019-01-01T00:00:00+00:00

EOT
        , file_get_contents($this->workspace()->path(self::EXAMPLE_FILEANME)));
    }

    public function testPublishesToCsvFileWithHeaders()
    {
        $report = ReportBuilder::forUrl(Url::fromUrl('https://www.dantleech.com'))
            ->withTimestamp(new DateTimeImmutable('2019-01-01 00:00:00 +00:00'))
            ->withStatus(200)
            ->build();

        $serialized = $this->create(true)->publish($report);
        $this->assertContains(<<<'EOT'
distance,exception,referrer,referrer_title,referrer_xpath,request_time,status,url,timestamp
0,,,,,0,200,https://www.dantleech.com,2019-01-01T00:00:00+00:00

EOT
        , file_get_contents($this->workspace()->path(self::EXAMPLE_FILEANME)));
    }

    private function create(bool $withHeaders = false): Publisher
    {
        $resource = fopen($this->workspace()->path(self::EXAMPLE_FILEANME), 'w');
        return new CsvStreamPublisher($resource, $withHeaders);
    }
}
