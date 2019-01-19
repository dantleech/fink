<?php

namespace DTL\Extension\Fink\Tests\Unit\Model\Queue;

use DTL\Extension\Fink\Model\Queue\DedupeQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\Url;
use PHPUnit\Framework\TestCase;

class DedupeQueueTest extends TestCase
{
    public function testDoesNotSufferDuplicates()
    {
        $queue = new DedupeQueue(new RealUrlQueue());

        $queue->enqueue(Url::fromUrl('https://www.dantleech.com'));
        $this->assertCount(1, $queue);

        $queue->enqueue(Url::fromUrl('https://www.dantleech.com'));
        $this->assertCount(1, $queue);

        $queue->enqueue(Url::fromUrl('https://www.foobar.com'));
        $this->assertCount(2, $queue);

        $url = $queue->dequeue();
        $this->assertEquals('https://www.dantleech.com', $url->__toString());
    }
}
