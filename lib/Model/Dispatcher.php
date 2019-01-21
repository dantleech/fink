<?php

namespace DTL\Extension\Fink\Model;

use Amp\Artax\HttpException;
use Amp\Dns\ResolutionException;
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
     * @var CircularReportStore
     */
    private $store;

    public function __construct(
        int $maxConcurrency,
        Publisher $publisher,
        Crawler $crawler,
        UrlQueue $queue,
        CircularReportStore $store
    ) {
        $this->crawler = $crawler;
        $this->publisher = $publisher;
        $this->status = new Status();
        $this->maxConcurrency = $maxConcurrency;
        $this->queue = $queue;
        $this->store = $store;
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

        $promise = \Amp\asyncCall(function (Url $url) {
            $this->status->nbConcurrentRequests++;

            try {
                $reportBuilder = ReportBuilder::forUrl($url);
                yield from $this->crawler->crawl($url, $this->queue, $reportBuilder);
            } catch (Exception $exception) {
                $reportBuilder->withException($exception);
            }

            $report = $reportBuilder->build();
            $this->publisher->publish($report);
            $this->store->add($report);

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

    /**
     * @return CircularReportStore<Report>
     */
    public function store(): CircularReportStore
    {
        return $this->store;
    }
}
