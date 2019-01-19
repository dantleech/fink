<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\Model\Publisher\BlackholePublisher;

class Runner
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

    public function __construct(
        int $maxConcurrency,
        Publisher $publisher = null,
        Crawler $crawler = null,
        Status $status = null
    ) {
        $this->crawler = $crawler ?: new Crawler();
        $this->publisher = $publisher ?: new BlackholePublisher();
        $this->status = $status ?: new Status();
        $this->maxConcurrency = $maxConcurrency;
    }

    public function run(UrlQueue $queue)
    {
        if ($this->status->nbConcurrentRequests >= $this->maxConcurrency) {
            return;
        }

        $url = $queue->dequeue();

        if (null === $url) {
            return;
        }

        \Amp\asyncCall(function (Url $url) use ($queue) {
            $this->status->nbConcurrentRequests++;

            $reportBuilder = ReportBuilder::forUrl($url);
            yield from $this->crawler->crawl($url, $queue, $reportBuilder);
            $report = $reportBuilder->build();
            $this->publisher->publish($report);

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
