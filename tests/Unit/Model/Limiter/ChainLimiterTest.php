<?php

namespace DTL\Extension\Fink\Tests\Unit\Model\Limiter;

use DTL\Extension\Fink\Model\Limiter;
use DTL\Extension\Fink\Model\Limiter\ChainLimiter;
use DTL\Extension\Fink\Model\Status;
use PHPUnit\Framework\TestCase;

class ChainLimiterTest extends TestCase
{
    /**
     * @var Status
     */
    private $status;

    /**
     * @var ObjectProphecy
     */
    private $limiter1;

    public function setUp()
    {
        $this->status = new Status();
        $this->limiter1 = $this->prophesize(Limiter::class);
    }

    public function testReturnsFalseWithNoLimiters()
    {
        $chainLimiter = new ChainLimiter([]);
        $this->assertEquals(false, $chainLimiter->limitReached($this->status));
    }

    public function testReturnsTrueIfInnerLimiterReturnsTrue()
    {
        $chainLimiter = new ChainLimiter([
            $this->limiter1->reveal()
        ]);

        $this->limiter1->limitReached($this->status)->willReturn(true);
        $this->assertEquals(true, $chainLimiter->limitReached($this->status));
    }

    public function testReturnsFalseIfInnerLimiterReturnsFalse()
    {
        $chainLimiter = new ChainLimiter([
            $this->limiter1->reveal()
        ]);

        $this->limiter1->limitReached($this->status)->willReturn(false);
        $this->assertEquals(false, $chainLimiter->limitReached($this->status));
    }
}
