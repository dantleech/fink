<?php

namespace DTL\Extension\Fink\Tests\Unit\Console\Display;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Formatter\OutputFormatter;

abstract class DisplayTestCase extends TestCase
{
    /**
     * @var OutputFormatter
     */
    protected $formatter;

    public function setUp()
    {
        $this->formatter = new OutputFormatter();
    }
}
