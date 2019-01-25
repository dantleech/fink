<?php
namespace DTL\Extension\Fink\Tests\Unit\Model\Store;

use DTL\Extension\Fink\Model\Store\CircularReportStore;
use DTL\Extension\Fink\Model\HttpStatusCode;
use DTL\Extension\Fink\Model\Report;
use DTL\Extension\Fink\Model\Url;
use PHPUnit\Framework\TestCase;

class CircularReportStoreTest extends TestCase
{
    public function testDoesNotAddMoreThanSize()
    {
        $store = new CircularReportStore(3);
        $store->add($this->createReport(1));
        $this->assertCount(1, $store);

        $store->add($this->createReport(2));
        $this->assertCount(2, $store);

        $store->add($this->createReport(3));
        $this->assertCount(3, $store);
        $store->add($this->createReport(4));
        $store->add($this->createReport(4));
        $this->assertCount(3, $store);
    }

    public function testIsIterable()
    {
        $store = new CircularReportStore(3);
        $store->add($this->createReport(1));
        $store->add($this->createReport(2));
        $store->add($this->createReport(3));

        $reports = [];
        foreach ($store as $report) {
            $reports[] = $report->url()->__toString();
        }

        $this->assertEquals([
            'https://www.example1.com',
            'https://www.example2.com',
            'https://www.example3.com',
        ], $reports);
    }

    private function createReport(int $int)
    {
        return new Report(Url::fromUrl('https://www.example' . $int.'.com'), HttpStatusCode::fromInt(200));
    }
}
