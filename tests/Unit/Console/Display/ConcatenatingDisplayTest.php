<?php

namespace DTL\Extension\Fink\Tests\Unit\Console\Display;

use DTL\Extension\Fink\Console\Display;
use DTL\Extension\Fink\Console\Display\ConcatenatingDisplay;
use DTL\Extension\Fink\Model\Dispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;

class ConcatenatingDisplayTest extends DisplayTestCase
{
    /**
     * @var ObjectProphecy|Display
     */
    private $display1;

    /**
     * @var ObjectProphecy|Display
     */
    private $display2;

    public function setUp()
    {
        parent::setUp();

        $this->display1 = $this->prophesize(Display::class);
        $this->display2 = $this->prophesize(Display::class);
    }

    public function testReturnsEmptyStringWithNoDisplays()
    {
        $display = $this->create([]);
        $output = $display->render($this->formatter, $this->dispatcher->reveal());
        $this->assertEquals('', $output);
    }

    public function testConcatenatesTheOutputOfOtherDisplays()
    {
        $this->display1->render($this->formatter, $this->dispatcher->reveal())->willReturn('foo');
        $this->display2->render($this->formatter, $this->dispatcher->reveal())->willReturn('bar');

        $display = $this->create([
            $this->display1->reveal(),
            $this->display2->reveal(),
        ]);
        $output = $display->render($this->formatter, $this->dispatcher->reveal());
        $this->assertEquals(<<<'EOT'
foo
bar
EOT
, $output);
    }

    private function create(array $array): Display
    {
        return new ConcatenatingDisplay($array);
    }
}
