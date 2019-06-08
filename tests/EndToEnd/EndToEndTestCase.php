<?php

namespace DTL\Extension\Fink\Tests\EndToEnd;

use DTL\Extension\Fink\Console\Command\CrawlCommand;
use DTL\Extension\Fink\Tests\IntegrationTestCase;
use RuntimeException;
use Symfony\Component\Process\Process;

abstract class EndToEndTestCase extends IntegrationTestCase
{
    protected function execute(array $args, string $project = 'website'): Process
    {
        $server = $this->serve($project);

        $fink = $this->finkProcess($args);

        $fink->run(function ($error, $data) {
            if (getenv('FINK_DEBUG')) {
                fwrite(STDERR, $data);
            }
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
        if (!in_array($process->getExitCode(), [ CrawlCommand::EXIT_STATUS_FAILURE, CrawlCommand::EXIT_STATUS_SUCCESS ])) {
            throw new RuntimeException(sprintf(
                'Process exited with code "%s": STDOUT: %s STDERR: %s',
                $process->getExitCode(),
                $process->getOutput(),
                $process->getErrorOutput()
            ));
        }

        $this->addToAssertionCount(1);
    }

    protected function finkProcess(array $args): Process
    {
        return new Process(array_merge([
            'bin/fink'
        ], $args), __DIR__ . '/../..');
    }
}
