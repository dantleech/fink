<?php

namespace DTL\Extension\Fink\Tests\Unit\Console\Display;

use DTL\Extension\Fink\Console\Display\MemoryDisplay;
use DTL\Extension\Fink\Model\Status;
use Symfony\Component\Console\Helper\FormatterHelper;

class MemoryDisplayTest extends DisplayTestCase
{
    public function testShowsMemoryUsage()
    {
        $display = new MemoryDisplay();
        $output = $display->render($this->formatter, new Status());

        $this->assertContains(<<<'EOT'
Memory
EOT
        , FormatterHelper::removeDecoration($this->formatter, $output));
    }
}
