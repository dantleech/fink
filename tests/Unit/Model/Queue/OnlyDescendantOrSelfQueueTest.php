<?php

namespace DTL\Extension\Fink\Tests\Unit\Model\Queue;

use DTL\Extension\Fink\Model\Queue\OnlyDescendantOrSelfQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\Url;
use PHPUnit\Framework\TestCase;

class OnlyDescendantOrSelfQueueTest extends TestCase
{
    public function testDoesNotEnqeueParents()
    {
        $queue = new OnlyDescendantOrSelfQueue(new RealUrlQueue(), Url::fromUrl('https://www.dantleech.com'));

        $queue->enqueue(Url::fromUrl('https://www.foobar.com'));
        $this->assertCount(0, $queue);
        $queue->enqueue(Url::fromUrl('https://www.dantleech.com/1234'));
        $this->assertCount(1, $queue);
    }
}
