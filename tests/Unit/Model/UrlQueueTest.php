<?php

namespace DTL\Extension\Fink\Tests\Unit\Model;

use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use PHPUnit\Framework\TestCase;

class UrlQueueTest extends TestCase
{
    public function testEnqueueDequeue()
    {
        $url = Url::fromUrl('http://www.dantleech.com');
        $queue = new RealUrlQueue();
        $queue->enqueue($url);
        $this->assertSame($url, $queue->dequeue());
    }

    public function testEnqueueMultiple()
    {
        $url1 = Url::fromUrl('http://www.dantleech.com');
        $url2 = Url::fromUrl('http://www.example.com');

        $queue = new RealUrlQueue();
        $queue->enqueue($url1);
        $queue->enqueue($url2);

        $this->assertCount(2, $queue);
        $this->assertSame($url1, $queue->dequeue());
        $this->assertSame($url2, $queue->dequeue());
        $this->assertCount(0, $queue);
    }

    public function testReturnsNullIfEmpty()
    {
        $queue = new RealUrlQueue();
        $this->assertNull($queue->dequeue());
    }
}
