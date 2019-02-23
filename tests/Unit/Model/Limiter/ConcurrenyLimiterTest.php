<?php

namespace DTL\Extension\Fink\Tests\Unit\Model\Limiter;

use DTL\Extension\Fink\Model\Limiter\ConcurrenyLimiter;
use DTL\Extension\Fink\Model\Status;
use PHPUnit\Framework\TestCase;

class ConcurrenyLimiterTest extends TestCase
{
    /**
     * @dataProvider provideLimitsConcurrency
     */
    public function testLimitsConcurrency(int $concurrency, int $max, bool $limitReached)
    {
        $limiter = new ConcurrenyLimiter($max);
        $status = new Status();
        $status->nbConcurrentRequests = $concurrency;

        $this->assertEquals($limitReached, $limiter->limitReached($status));
    }

    public function provideLimitsConcurrency()
    {
        yield [
            10,
            10,
            true
        ];

        yield [
            10,
            9,
            true
        ];

        yield [
            10,
            11,
            false
        ];

        yield [
            0,
            0,
            true
        ];
    }
}
