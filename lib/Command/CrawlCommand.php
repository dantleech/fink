<?php

namespace DTL\Extension\Fink\Command;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Loop;
use DTL\Extension\Fink\Model\Crawler;
use DTL\Extension\Fink\Model\Publisher\StreamPublisher;
use DTL\Extension\Fink\Model\Queue\DedupeQueue;
use DTL\Extension\Fink\Model\Queue\OnlyDescendantOrSelfQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\Runner;
use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlQueue;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlCommand extends Command
{
    const ARG_URL = 'url';

    const OPT_DESCENDANTS_ONLY = 'descendants-only';
    const OPT_NO_DEDUPE = 'no-dedupe';
    const OPT_CONCURRENCY = 'concurrency';

    const DISPLAY_POLL_TIME = 100;
    const RUNNER_POLL_TIME = 10;

    const EXIT_STATUS_FAILURE = 1;
    const EXIT_STATUS_SUCCESS = 0;

    /**
     * @var Crawler
     */
    private $crawler;

    public function __construct()
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument(
            self::ARG_URL,
            InputArgument::REQUIRED,
            'URL to crawl'
        );

        $this->addOption(
            self::OPT_CONCURRENCY,
            'c',
            InputOption::VALUE_REQUIRED,
            'Concurrency',
            self::RUNNER_POLL_TIME
        );

        $this->addOption(
            'output',
            'o',
            InputOption::VALUE_REQUIRED,
            'Output file'
        );

        $this->addOption(
            self::OPT_NO_DEDUPE,
            null,
            InputOption::VALUE_NONE,
            'Do not de-duplicate URLs'
        );

        $this->addOption(
            self::OPT_DESCENDANTS_ONLY,
            null,
            InputOption::VALUE_NONE,
            'Only crawl descendants of the given path'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ConsoleOutput);

        $url = Url::fromUrl((string) $input->getArgument('url'));

        $queue = $this->buildQueue($input, $url);
        $queue->enqueue($url);

        $runner = $this->buildRunner($input);

        Loop::repeat(self::RUNNER_POLL_TIME, function () use ($runner, $queue) {
            $runner->run($queue);
        });

        $section = $output->section();

        Loop::repeat(self::DISPLAY_POLL_TIME, function () use ($section, $runner, $queue) {
            $section->overwrite(sprintf(
                '<comment>Concurrency</>: %s, <comment>URL queue size</>: %s, <comment>Failures</>: %s/%s (%s%%)' . PHP_EOL .
                '%s',
                $runner->status()->nbConcurrentRequests,
                $queue->count(),
                $runner->status()->nbFailures,
                $runner->status()->requestCount,
                number_format($runner->status()->failurePercentage(), 2),
                $runner->status()->lastUrl,
            ));

            if ($runner->status()->nbConcurrentRequests === 0 && $queue->count() === 0) {
                Loop::stop();

                if ($runner->status()->nbFailures) {
                    return self::EXIT_STATUS_FAILURE;
                }
            }
        });

        Loop::run();
        return self::EXIT_STATUS_SUCCESS;
    }

    private function buildQueue(InputInterface $input, Url $url): UrlQueue
    {
        $queue = new RealUrlQueue();
        
        if (!$input->getOption(self::OPT_NO_DEDUPE)) {
            $queue = new DedupeQueue($queue);
        }
        
        if ($input->getOption(self::OPT_DESCENDANTS_ONLY)) {
            $queue = new OnlyDescendantOrSelfQueue($queue, $url);
        }
        return $queue;
    }

    private function buildRunner(InputInterface $input): Runner
    {
        $maxConcurrency = (int) $input->getOption(self::OPT_CONCURRENCY);
        $publisher = null;

        if ($outfile = $input->getOption('output')) {
            $stream = new ResourceOutputStream(fopen($outfile, 'w'));
            $publisher = new StreamPublisher($stream);
        }

        return new Runner($maxConcurrency, $publisher);
    }
}
