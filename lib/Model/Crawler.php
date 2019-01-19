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

    public function __construct(Client $client = null)
    {
        $this->client = $client ?: new DefaultClient();
    }

    public function crawl(Url $documentUrl, UrlQueue $queue): Generator
    {
        try {
            $response = yield $this->client->request($documentUrl->__toString());
        } catch (HttpException $e) {
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
                continue;
            }

            if (!$url->isHttp()) {
                continue;
            }

            $queue->enqueue($url);
        }
    }
}
