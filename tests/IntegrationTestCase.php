<?php

namespace DTL\Extension\Fink\Tests;

use Amp\PHPUnit\AsyncTestCase;
use Phpactor\TestUtils\Workspace;

class IntegrationTestCase extends AsyncTestCase
{
    /**
     * @var Workspace
     */
    private $workspace;

    public function setUp(): void
    {
        parent::setUp();

        $this->workspace = Workspace::create(__DIR__ . '/Workspace');
        $this->workspace->reset();
    }

    protected function workspace(): Workspace
    {
        return $this->workspace;
    }
}
