<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\Model\Store\ImmutableReportStore;
use DTL\Extension\Fink\Model\ImmutableReportStore as ImmutableReportStoreInterface;
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

    /**
     * @var Limiter
     */
    private $limiter;

    public function __construct(
        Publisher $publisher,
        Crawler $crawler,
        UrlQueue $queue,
        ReportStore $store,
        Limiter $limiter
    ) {
        $this->crawler = $crawler;
        $this->publisher = $publisher;
        $this->queue = $queue;
        $this->store = $store;
        $this->status = new Status(new ImmutableReportStore($store));
        $this->limiter = $limiter;
    }

    public function dispatch()
    {
        while (true) {
            if ($this->limiter->limitReached($this->status)) {
                return;
            }
            
            $url = $this->queue->dequeue();
            
            if (null === $url) {
                return;
            }

            $this->doDispatch($url);
        }
    }

    private function doDispatch(Url $url): void
    {
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
     * @return ImmutableReportStoreInterface<Report>
     */
    public function store(): ImmutableReportStoreInterface
    {
        return new ImmutableReportStore($this->store);
    }
}
