<?php

namespace DTL\Extension\Fink\Tests\Unit\Model\Queue;

use DTL\Extension\Fink\Model\Queue\ExternalDistanceLimitingQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\Url;
use PHPUnit\Framework\TestCase;

class ExternalDisatanceLimitingQueueTest extends TestCase
{
    public function testLimitsExternalDistanceToZero()
    {
        $queue = new ExternalDistanceLimitingQueue(new RealUrlQueue(), 0);

        $url = Url::fromUrl('https://www.dantleech.com');
        $internalUrl = $url->resolveUrl('https://www.dantleech.com/1234');
        $externalUrl = $internalUrl->resolveUrl('https://foobar.com');
        $externalUrlChild = $externalUrl->resolveUrl('https://foobar.com/test');

        $queue->enqueue($internalUrl);
        $this->assertCount(1, $queue);
        $queue->enqueue($externalUrl);
        $this->assertCount(1, $queue);
    }

    public function testLimitsExternalDistanceToOne()
    {
        $url = Url::fromUrl('https://www.dantleech.com');

        $queue = new ExternalDistanceLimitingQueue(new RealUrlQueue(), 1);

        $internalUrl = $url->resolveUrl('https://www.dantleech.com/1234');
        $externalUrl = $internalUrl->resolveUrl('https://foobar.com');
        $externalUrlChild = $externalUrl->resolveUrl('https://foobar.com/test');

        $queue->enqueue($internalUrl);
        $this->assertCount(1, $queue);
        $queue->enqueue($externalUrl);
        $this->assertCount(2, $queue);

        $queue->enqueue($externalUrlChild);
        $this->assertCount(2, $queue);
    }

    public function testLimitsExternalDistanceToTwo()
    {
        $url = Url::fromUrl('https://www.dantleech.com');

        $queue = new ExternalDistanceLimitingQueue(new RealUrlQueue(), 2);

        $one = $url->resolveUrl('https://foobar.com');
        $two = $one->resolveUrl('https://foobar.com/test');

        $queue->enqueue($one);
        $this->assertCount(1, $queue);
        $queue->enqueue($two);
        $this->assertCount(2, $queue);
    }
}
