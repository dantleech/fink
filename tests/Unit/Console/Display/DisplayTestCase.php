<?php

namespace DTL\Extension\Fink\Tests\Unit\Console\Display;

use DTL\Extension\Fink\Model\Dispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;

abstract class DisplayTestCase extends TestCase
{
    /**
     * @var OutputFormatter
     */
    protected $formatter;

    /**
     * @var ObjectProphecy
     */
    protected $dispatcher;

    public function setUp()
    {
        $this->formatter = new OutputFormatter();
        $this->dispatcher = $this->prophesize(Dispatcher::class);
    }
}
