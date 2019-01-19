<?php

namespace DTL\Extension\Fink\Command;

use Amp\Loop;
use DTL\Extension\Fink\Model\Crawler;
use DTL\Extension\Fink\Model\Queue\DedupeQueue;
use DTL\Extension\Fink\Model\Queue\OnlyDescendantOrSelfQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\Runner;
use DTL\Extension\Fink\Model\Url;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlCommand extends Command
{
    const ARG_URL = 'url';
    const DISPLAY_POLL_TIME = 100;
    const OPT_DESCENDANTS_ONLY = 'descendants-only';
    const OPT_NO_DEDUPE = 'no-dedupe';
    const OPT_CONCURRENCY = 'concurrency';

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
        $this->addArgument(self::ARG_URL, InputArgument::REQUIRED, 'URL to crawl');

        $this->addOption(self::OPT_CONCURRENCY, 'c', InputOption::VALUE_REQUIRED, 'Concurrency', 10);
        $this->addOption(self::OPT_NO_DEDUPE, null, InputOption::VALUE_NONE, 'Do not de-duplicate URLs');
        $this->addOption(self::OPT_DESCENDANTS_ONLY, null, InputOption::VALUE_NONE, 'Only crawl descendants of the given path');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ConsoleOutput);

        $url = Url::fromUrl((string) $input->getArgument('url'));

        $queue = $this->buildQueue($input, $url);
        $queue->enqueue($url);

        $maxConcurrency = (int) $input->getOption(self::OPT_CONCURRENCY);
        $runner = new Runner($maxConcurrency);

        Loop::repeat(50, function () use ($runner, $queue) {
            $runner->run($queue);
        });

        $section = $output->section();

        Loop::repeat(self::DISPLAY_POLL_TIME, function () use ($section, $runner, $queue) {
            static $spinner = 0;

            $spinnerStates = ['-','/', '-', '\\'];

            $section->overwrite(sprintf(
                '%s Requests: %s, Concurrency: %s, URL queue size: %s %s',
                $spinnerStates[$spinner % 4],
                $runner->status()->requestCount,
                $runner->status()->concurrentRequests,
                $queue->count(),
                $spinnerStates[$spinner++ % count($spinnerStates)],
            ));
        });

        Loop::run();
    }
}

class Command
{
    private function buildQueue(InputInterface $input, Url $url): OnlyDescendantOrSelfQueue
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
}
