<?php

namespace DTL\Extension\Fink\Model;

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

    public function __construct(
        int $maxConcurrency,
        Crawler $crawler = null,
        Status $status = null
    ) {
        $this->crawler = $crawler ?: new Crawler();
        $this->status = $status ?: new Status();
        $this->maxConcurrency = $maxConcurrency;
    }

    public function run(UrlQueue $queue)
    {
        if ($this->status->concurrentRequests >= $this->maxConcurrency) {
            return;
        }

        $url = $queue->dequeue();

        if (null === $url) {
            return;
        }

        \Amp\asyncCall(function (Url $url) use ($queue) {
            $this->status->concurrentRequests++;

            yield from $this->crawler->crawl($url, $queue);

            $this->status->requestCount++;
            $this->status->concurrentRequests--;
        }, $url);
    }

    public function status(): Status
    {
        return $this->status;
    }
}
