<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\Model\Publisher\BlackholePublisher;

class Dispatcher
{
    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @var Status
     */
    private $status;

    /**
     * @var int
     */
    private $maxConcurrency;

    /**
     * @var Publisher
     */
    private $publisher;

    /**
     * @var UrlQueue
     */
    private $queue;

    public function __construct(
        int $maxConcurrency,
        Publisher $publisher,
        Crawler $crawler,
        UrlQueue $queue
    ) {
        $this->crawler = $crawler;
        $this->publisher = $publisher;
        $this->status = new Status();
        $this->maxConcurrency = $maxConcurrency;
        $this->queue = $queue;
    }

    public function dispatch()
    {
        if ($this->status->nbConcurrentRequests >= $this->maxConcurrency) {
            return;
        }

        $url = $this->queue->dequeue();

        if (null === $url) {
            return;
        }

        \Amp\asyncCall(function (Url $url) {
            $this->status->nbConcurrentRequests++;

            $reportBuilder = ReportBuilder::forUrl($url);
            yield from $this->crawler->crawl($url, $this->queue, $reportBuilder);
            $report = $reportBuilder->build();
            $this->publisher->publish($report);

            $this->status->queueSize = count($this->queue);
            $this->status->nbFailures += $report->isSuccess() ? 0 : 1;
            $this->status->lastUrl = $url->__toString();
            $this->status->requestCount++;
            $this->status->nbConcurrentRequests--;
        }, $url);
    }

    public function status(): Status
    {
        return $this->status;
    }
}
