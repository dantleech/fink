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
use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlFactory;
use DTL\Extension\Fink\Model\UrlQueue;
use Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlCommand extends Command
{
    const ARG_URL = 'url';

    /**
     * @var DefaultClient
     */
    private $client;

    public function __construct()
    {
        parent::__construct();
        $this->client = new DefaultClient();
    }

    protected function configure()
    {
        $this->addArgument(self::ARG_URL, InputArgument::REQUIRED, 'URL to crawl');
        $this->addOption('concurrency', 'c', InputOption::VALUE_REQUIRED, 'Concurrency', 10);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $urlPromise = $input->getArgument('url');
        $maxConcurrency = (int) $input->getOption('concurrency');
        $queue = new RealUrlQueue();

        if (true) {
            $queue = new DedupeQueue($queue);
        }

        $documentUrl = UrlFactory::fromUrl($urlPromise);
        $queue->enqueue($documentUrl);
        $concurrency = 0;
        $requestCount = 0;

        Loop::repeat(50, function () use ($queue, $output, $maxConcurrency, &$concurrency, &$requestCount){
            while ($concurrency < $maxConcurrency && $url = $queue->dequeue()) {

                \Amp\asyncCall(function (Url $documentUrl) use ($output, $queue, &$concurrency, &$requestCount) {
                    $concurrency++;

                    $queue = yield from $this->crawl($output, $documentUrl, $queue);

                    $requestCount++;
                    $concurrency--;
                }, $url);
            }
        });

        assert($output instanceof ConsoleOutput);
        $section = $output->section();

        Loop::repeat(100, function () use ($section, $queue, &$concurrency, &$requestCount) {
            $section->overwrite(sprintf(
                'Requests: %s, Concurrency: %s, URL queue size: %s',
                $requestCount,
                $concurrency,
                $queue->count()
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

        //$output->writeln(sprintf(
        //    '<%s>%s</>: %s',
        //    $response->getStatus() == 200 ? 'info':'error',
        //    $response->getStatus(),
        //    $documentUrl->__toString()
        //));

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
