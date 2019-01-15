<?php

namespace DTL\Extension\Fink\Command;

use Amp\Artax\DefaultClient;
use Amp\Artax\Response;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use Amp\Uri\Uri;
use DOMDocument;
use DOMXPath;
use DTL\Extension\Fink\Model\Crawler;
use Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        $this->addOption('concurrency');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $url = $input->getArgument('url');

        $promise = \Amp\call(function () use ($url, $output) {
            return $this->crawl($output, $url);
        });
        $urls = \Amp\Promise\wait($promise);

        $promises = [];
        foreach ($urls as $url) {
            $output->write('.');
            $promises[] = \Amp\call(function () use ($url, $output) {
                return $this->crawl($output, $url);
            });
        }
        $output->writeln('');
        \Amp\Promise\wait(\Amp\Promise\all($promises));
    }

    private function crawl(OutputInterface $output, string $url): Generator
    {
        if (!Uri::isValid($url)) {
            return;
        }

        $requestedUri = new Uri($url);
        $response = yield $this->client->request($url);
        assert($response instanceof Response);
        $output->writeln(sprintf('<info>%s</>: %s', $response->getStatus(), $url));
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

            $url = parse_url($href);

            if (!isset($url['path'])) {
                $href = $requestedUri->getPath() . $href;
            }

            if (!isset($url['host'])) {
                $href = $requestedUri->getHost() . '/' . ltrim($href, '/');
            }

            if (!isset($url['scheme'])) {
                $href = $requestedUri->getScheme() . '://' . $href; 
            }

            $href = rtrim($href, '/');

            if (substr($href, 0, 4) !== 'http') {
                continue;
            }

            $linkUrls[] = $href;
        }

        return $linkUrls;
    }
}
