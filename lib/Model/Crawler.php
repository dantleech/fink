<?php

namespace DTL\Extension\Fink\Model;

use Amp\Artax\Client;
use Amp\Artax\Response;
use DOMDocument;
use DOMElement;
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
        $start = microtime(true);
        $report->withReferringElement($documentUrl->referringElement());
        $response = yield $this->client->request($documentUrl->__toString());
        $time = (microtime(true) - $start) * 1E6;

        $report->withRequestTime((int) $time);

        assert($response instanceof Response);
        $report->withStatus($response->getStatus());

        $body = '';
        while ($chunk = yield $response->getBody()->read()) {
            $body .= $chunk;
        }

        $this->enqueueLinks($this->loadXpath($body), $documentUrl, $report, $queue);
    }

    private function enqueueLinks(DOMXPath $xpath, Url $documentUrl, ReportBuilder $report, UrlQueue $queue): void
    {
        foreach ($xpath->query('//a') as $linkElement) {
            assert($linkElement instanceof DOMElement);
            $href = $linkElement->getAttribute('href');
        
            if (!$href) {
                continue;
            }
        
            try {
                $url = $documentUrl->resolveUrl($href, ReferringElement::fromDOMNode($linkElement));
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

    private function loadXpath(string $body): DOMXPath
    {
        $dom = new DOMDocument('1.0');
        @$dom->loadHTML($body);

        return new DOMXPath($dom);
    }
}
