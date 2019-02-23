<?php

namespace DTL\Extension\Fink\Tests\Unit\Model\Limiter;

use DTL\Extension\Fink\Model\Limiter;
use DTL\Extension\Fink\Model\Limiter\Exception\InvalidRate;
use DTL\Extension\Fink\Model\Limiter\RateLimiter;
use DTL\Extension\Fink\Model\Status;
use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase
{
    /**
     * @var Status
     */
    private $status;

    public function setUp()
    {
        $this->status = new Status();
    }

    public function testReturnsFalseImmediately()
    {
        $this->assertFalse($this->create()->limitReached($this->status));
    }

    /**
     * @dataProvider provideLimitsRate
     */
    public function testLimitsRate(float $rate, float $secondsToRun, int $expectedHits)
    {
        $end = microtime(true) + $secondsToRun;
        $hits = 0;

        $limiter = $this->create($rate);
        while (microtime(true) <= $end) {
            if (false === $limiter->limitReached($this->status)) {
                $hits++;
            }

            usleep(500);
        }

        $this->assertEquals($expectedHits, $hits, 'Expected number of hits', 1);
    }

    public function provideLimitsRate()
    {
        yield [ 4.0, 0.25, 1 ];
        yield [ 100, 0.25, 25 ];
    }

    public function testExceptionOnRateZero()
    {
        $this->expectException(InvalidRate::class);
        $this->create(0);
    }

    public function testExceptionOnNegative()
    {
        $this->expectException(InvalidRate::class);
        $this->create(-10);
    }

    private function create(float $rate = 100): Limiter
    {
        return new RateLimiter($rate);
    }
}
