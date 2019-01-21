<?php

namespace DTL\Extension\Fink\Tests\Unit\Model;

use DTL\Extension\Fink\Model\CircularUrlStore;
use DTL\Extension\Fink\Model\Url;
use PHPUnit\Framework\TestCase;

class CircularUrlStoreTest extends TestCase
{
    public function testDoesNotAddMoreThanSize()
    {
        $store = new CircularUrlStore(3);
        $store->add(Url::fromUrl('https://www.example.com'));
        $this->assertCount(1, $store);

        $store->add(Url::fromUrl('https://www.example.com'));
        $this->assertCount(2, $store);

        $store->add(Url::fromUrl('https://www.example.com'));
        $this->assertCount(3, $store);
        $store->add(Url::fromUrl('https://www.example.com'));
        $store->add(Url::fromUrl('https://www.example.com'));
        $this->assertCount(3, $store);
    }

    public function testIsIterable()
    {
        $store = new CircularUrlStore(3);
        $store->add(Url::fromUrl('https://www.example1.com'));
        $store->add(Url::fromUrl('https://www.example2.com'));
        $store->add(Url::fromUrl('https://www.example3.com'));

        $urls = [];
        foreach ($store as $url) {
            $urls[] = $url->__toString();
        }

        $this->assertEquals([
            'https://www.example1.com',
            'https://www.example2.com',
            'https://www.example3.com',
        ], $urls);

    }
}
