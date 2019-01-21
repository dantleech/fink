<?php

namespace DTL\Extension\Fink\Tests\Unit\Model\Queue;

use DTL\Extension\Fink\Model\Queue\FirstExternalOnlyQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\Url;
use PHPUnit\Framework\TestCase;

class FirstExternalOnlyQueueTest extends TestCase
{
    public function testDoesNotEnqeueParents()
    {
        $url = Url::fromUrl('https://www.dantleech.com');

        $queue = new FirstExternalOnlyQueue(new RealUrlQueue(), $url);

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
}
