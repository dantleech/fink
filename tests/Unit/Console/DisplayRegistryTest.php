<?php

namespace DTL\Extension\Fink\Tests\Unit\Console;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Console\DisplayRegistry;
use DTL\Extension\Fink\Console\Exception\DisplayNotFound;
use PHPUnit\Framework\TestCase;

class DisplayRegistryTest extends TestCase
{
    public function testItThrowsExceptionIfDisplayNodeFound()
    {
        $this->expectException(DisplayNotFound::class);
        $this->createRegistry()->get('foo');
    }

    public function testItReturnsRegisteredDisplays()
    {
        $expected = $this->prophesize(Display::class)->reveal();
        $display = $this->createRegistry([
            'one' => $expected
        ])->get('one');

        $this->assertSame($expected, $display);
    }

    private function createRegistry(array $displays = []): DisplayRegistry
    {
        return new DisplayRegistry($displays);
    }
}
