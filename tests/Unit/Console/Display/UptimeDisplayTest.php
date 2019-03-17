<?php

namespace DTL\Extension\Fink\Tests\Unit\Console\Display;

use DTL\Extension\Fink\Console\Display\UptimeDisplay;
use DTL\Extension\Fink\Model\Status;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Helper\FormatterHelper;

class UptimeDisplayTest extends DisplayTestCase
{
    /**
     * @dataProvider provideShowsUptime
     */
    public function testShowsUptime($uptime, $expected)
    {
        $uptime = new UptimeDisplay((new DateTimeImmutable())->modify('-' . $uptime . ' seconds'));
        $status = $uptime->render($this->formatter, new Status());
        $this->assertEquals($expected, FormatterHelper::removeDecoration($this->formatter, $status));
    }

    public function provideShowsUptime()
    {
        yield [
            1, 'Up 00h 00m 01s',
        ];

        yield [
            60, 'Up 00h 01m 00s',
        ];

        yield [
            3600, 'Up 01h 00m 00s',
        ];
    }
}
