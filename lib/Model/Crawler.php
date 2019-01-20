<?php

namespace DTL\Extension\Fink\Model;

use Amp\Artax\Client;
use Amp\Artax\DefaultClient;
use Amp\Artax\HttpException;
use Amp\Artax\Response;
use DOMDocument;
use DOMXPath;
use DTL\Extension\Fink\Model\Exception\InvalidUrl;
use Generator;

class Crawler
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function crawl(Url $documentUrl, UrlQueue $queue, ReportBuilder $report): Generator
    {
        try {
            $start = microtime(true);
            $response = yield $this->client->request($documentUrl->__toString());
            $time = (microtime(true) - $start) * 1E6;
            $report->withRequestTime((int) $time);
        } catch (HttpException $e) {
            $report->withException($e);
            return;
        }

        assert($response instanceof Response);
        $report->withStatus($response->getStatus());

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
                $report->withException($invalidUrl);
                continue;
            }

            if (!$url->isHttp()) {
                continue;
            }

            $queue->enqueue($url);
        }
    }
}
