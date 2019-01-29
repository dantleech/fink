<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\Model\Store\ImmutableReportStore;
use Exception;

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

    /**
     * @var ?int
     */
    private $maxDistance;

    /**
     * @var ReportStore
     */
    private $store;

    public function __construct(
        int $maxConcurrency,
        Publisher $publisher,
        Crawler $crawler,
        UrlQueue $queue,
        ReportStore $store
    ) {
        $this->crawler = $crawler;
        $this->publisher = $publisher;
        $this->maxConcurrency = $maxConcurrency;
        $this->queue = $queue;
        $this->store = $store;

        $this->status = new Status();
        $this->status->reportStore = new ImmutableReportStore($this->store);
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

            try {
                yield from $this->crawler->crawl($url, $this->queue, $reportBuilder);
            } catch (Exception $exception) {
                $reportBuilder->withException($exception);
            }

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
