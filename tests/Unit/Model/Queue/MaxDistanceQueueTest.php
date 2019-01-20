<?php

namespace DTL\Extension\Fink\Tests\Unit\Model\Queue;

use DTL\Extension\Fink\Model\Queue\MaxDistanceQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\Url;
use PHPUnit\Framework\TestCase;

class MaxDistanceQueueTest extends TestCase
{
    public function testDoesNotEnqeueUrlBeyondMaxDistance()
    {
        $queue = new MaxDistanceQueue(new RealUrlQueue(), 1);

        $url = Url::fromUrl('https://www.foobar.com');
        $queue->enqueue($url);
        $this->assertCount(1, $queue);
        $oneStepRemoved = $url->resolveUrl('https://www.foobar.com/1');
        $queue->enqueue($oneStepRemoved);
        $this->assertCount(2, $queue);
        $twoStepsRemoved = $oneStepRemoved->resolveUrl('https://www.foobar.com/2');
        $queue->enqueue($twoStepsRemoved);
        $this->assertCount(2, $queue);
    }
}
