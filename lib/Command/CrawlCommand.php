<?php

namespace DTL\Extension\Fink\Command;

use Amp\Artax\DefaultClient;
use Amp\Artax\Response;
use Amp\Coroutine;
use Amp\Loop;
use Amp\Promise;
use Amp\Success;
use DOMDocument;
use DOMXPath;
use DTL\Extension\Fink\Model\Crawler;
use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlFactory;
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
        $urlPromise = $input->getArgument('url');
        $documentUrl = UrlFactory::fromUrl($urlPromise);

        // TODO: create URL stack
        $promise = \Amp\call(function () use ($output, $documentUrl) {
            $promises = [];
            $urls = yield from $this->crawl($output, $documentUrl);

            foreach ($urls as $url) {
                if (isset($seen[$url->__toString()])) {
                    continue;
                }

                $seen[$url->__toString()] = true;

                $promises[] = \Amp\call(function () use ($url, $output) {
                    return $this->crawl($output, $url);
                });
            }

            \Amp\Promise\wait(\Amp\Promise\all($promises));
        });

        \Amp\Promise\wait($promise);
    }

    private function crawl(OutputInterface $output, Url $documentUrl): Generator
    {
        $output->writeln($documentUrl->__toString());
        $response = yield $this->client->request($documentUrl->__toString());
        assert($response instanceof Response);
        $output->writeln(sprintf(
            '<%s>%s</>: %s',
            $response->getStatus() == 200 ? 'info':'error',
            $response->getStatus(),
            $documentUrl->__toString()
        ));
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

            $url = $documentUrl->resolveUrl($href);

            if (!$url->isHttp()) {
                continue;
            }

            $linkUrls[] = $url;
        }

        return $linkUrls;
    }
}
