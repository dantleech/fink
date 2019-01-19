<?php

namespace DTL\Extension\Fink\Command;

use Amp\Artax\DefaultClient;
use Amp\Artax\HttpException;
use Amp\Artax\Response;
use Amp\Coroutine;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use DOMDocument;
use DOMXPath;
use DTL\Extension\Fink\Model\Crawler;
use DTL\Extension\Fink\Model\Exception\InvalidUrl;
use DTL\Extension\Fink\Model\Queue\DedupeQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\Runner;
use DTL\Extension\Fink\Model\Status;
use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlFactory;
use DTL\Extension\Fink\Model\UrlQueue;
use Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlCommand extends Command
{
    const ARG_URL = 'url';
    const DISPLAY_POLL_TIME = 100;

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
        $this->addOption('concurrency', 'c', InputOption::VALUE_REQUIRED, 'Concurrency', 10);
        $this->addOption('no-dedupe', null, InputOption::VALUE_NONE, 'Do not de-duplicate URLs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = (string) $input->getArgument('url');
        $maxConcurrency = (int) $input->getOption('concurrency');

        $queue = new RealUrlQueue();

        if (!$input->getOption('no-dedupe')) {
            $queue = new DedupeQueue($queue);
        }

        $queue->enqueue(Url::fromUrl($url));

        $runner = new Runner($maxConcurrency);

        Loop::repeat(50, function () use ($runner, $queue) {
            $runner->run($queue);
        });

        assert($output instanceof ConsoleOutput);
        $section = $output->section();

        Loop::repeat(self::DISPLAY_POLL_TIME, function () use ($section, $runner, $queue) {
            static $spinner = 0;
            $status = ['-','/', '-', '\\'];

            $section->overwrite(sprintf(
                '%s Requests: %s, Concurrency: %s, URL queue size: %s %s',
                $status[$spinner % 4],
                $runner->status()->requestCount,
                $runner->status()->concurrentRequests,
                $queue->count(),
                $status[$spinner++ % 4],
            ));
        });

        Loop::run();
    }

    private function crawl(OutputInterface $output, Url $documentUrl, UrlQueue $queue): Generator
    {
        try {
            $response = yield $this->client->request($documentUrl->__toString());
        } catch (HttpException $e) {
            $output->writeln(sprintf('<error>%s</>', $e->getMessage()));
            return;
        }

        assert($response instanceof Response);

        $body = yield $response->getBody();
        $dom = new DOMDocument('1.0');

        @$dom->loadHTML($body);
        $xpath = new DOMXPath($dom);

        $linkUrls = [];
        foreach ($xpath->query('//a') as $linkElement) {

            $href = $linkElement->getAttribute('href');

            if (!$href) {
                continue;
            }

            try {
                $url = $documentUrl->resolveUrl($href);
            } catch (InvalidUrl $invalidUrl) {
                $output->writeln(sprintf('<error>%s</>', $invalidUrl->getMessage()), 0, $invalidUrl);
                continue;
            }

            if (!$url->isHttp()) {
                continue;
            }

            $queue->enqueue($url);
        }

        return $queue;
    }
}
