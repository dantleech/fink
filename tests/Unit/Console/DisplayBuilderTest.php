<?php

namespace DTL\Extension\Fink\Tests\Unit\Console;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Console\DisplayBuilder;
use DTL\Extension\Fink\Console\DisplayRegistry;
use DTL\Extension\Fink\Console\Display\ConcatenatingDisplay;
use PHPUnit\Framework\TestCase;

class DisplayBuilderTest extends TestCase
{
    /**
     * @var ObjectProphecy
     */
    private $registry;
    /**
     * @var ObjectProphecy
     */
    private $display1;
    /**
     * @var ObjectProphecy
     */
    private $display2;

    /**
     * @var DisplayBuilder
     */
    private $builder;

    public function setUp()
    {
        $this->registry = $this->prophesize(DisplayRegistry::class);
        $this->display1 = $this->prophesize(Display::class);
        $this->display2 = $this->prophesize(Display::class);
    }

    public function testSetsADisplay()
    {
        $this->registry->get('foo')->willReturn($this->display1->reveal());

        $display = $this->createBuilder(['bar'])->build('foo');
        $this->assertEquals(new ConcatenatingDisplay([
            $this->display1->reveal()
        ]), $display);
    }

    public function testConcatenatesMultipleDisplays()
    {
        $this->registry->get('foo')->willReturn($this->display1->reveal());
        $this->registry->get('bar')->willReturn($this->display2->reveal());

        $display = $this->createBuilder()->build('foo');
        $this->assertEquals(new ConcatenatingDisplay([
            $this->display1->reveal()
        ]), $display);
    }

    public function testAddsDisplayWithPlusPrefix()
    {
        $this->registry->get('foo')->willReturn($this->display1->reveal());
        $this->registry->get('bar')->willReturn($this->display2->reveal());

        $display = $this->createBuilder(['foo'])->build('+bar');
        $this->assertEquals(new ConcatenatingDisplay([
            $this->display1->reveal(),
            $this->display2->reveal()
        ]), $display);
    }

    public function testRemovesADisplayWithAMinusPrefix()
    {
        $this->registry->get('foo')->willReturn($this->display1->reveal());
        $this->registry->get('bar')->willReturn($this->display2->reveal());

        $display = $this->createBuilder(['foo', 'bar'])->build('-bar');
        $this->assertEquals(new ConcatenatingDisplay([
            $this->display1->reveal()
        ]), $display);
    }

    private function createBuilder(array $defaults = []): DisplayBuilder
    {
        return new DisplayBuilder($this->registry->reveal(), $defaults);
    }
}
