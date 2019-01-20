<?php

namespace DTL\Extension\Fink\Tests\EndToEnd;

use PHPUnit\Framework\TestCase;
use Phpactor\TestUtils\Workspace;
use RuntimeException;
use Symfony\Component\Process\Process;

class EndToEndTestCase extends TestCase
{
    /**
     * @var Workspace
     */
    private $workspace;

    public function setUp()
    {
        $this->workspace = Workspace::create(__DIR__ . '/../Workspace');
        $this->workspace->reset();
    }

    protected function workspace(): Workspace
    {
        return $this->workspace;
    }

    protected function execute(array $array): Process
    {
        $server = $this->serve('website');

        $fink = new Process(array_merge([
            'bin/fink'
        ], $array), __DIR__ . '/../..');

        $fink->run(function ($error, $data) {
            //fwrite(STDERR, $data);
        });

        $server->stop();

        return $fink;
    }

    protected function serve(string $project): Process
    {
        $process = new Process([
            'php',
            '-S',
            '127.0.0.1:8124',
        ], __DIR__ . '/../Example/' . $project);
        $process->start();

        $exitCode = $process->getExitCode();
        if ($exitCode) {
            throw new RuntimeException($process->getErrorOutput());
        }

        return $process;
    }

    protected function parseResults(string $path): array
    {
        $contents = file_get_contents($path);
        $lines = array_filter(explode(PHP_EOL, $contents));
        return array_map(function (string $line) {
            return json_decode($line, true);
        }, $lines);
    }

    protected function assertProcessSuccess(Process $process)
    {
        if ($process->getExitCode() !== 0) {
            throw new RuntimeException(sprintf(
                'Process exited with code "%s": STDOUT: %s STDERR: %s',
                $process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput()
            ));
        }

        $this->addToAssertionCount(1);
    }
}
