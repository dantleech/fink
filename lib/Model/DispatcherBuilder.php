<?php

namespace DTL\Extension\Fink\Model;

use Amp\Artax\Client;
use Amp\Artax\Cookie\NullCookieJar;
use Amp\Artax\DefaultClient;
use Amp\Artax\HttpSocketPool;
use Amp\ByteStream\ResourceOutputStream;
use Amp\Socket\ClientTlsContext;
use DTL\Extension\Fink\Model\Publisher\BlackholePublisher;
use DTL\Extension\Fink\Model\Publisher\StreamPublisher;
use DTL\Extension\Fink\Model\Queue\DedupeQueue;
use DTL\Extension\Fink\Model\Queue\OnlyDescendantOrSelfQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use RuntimeException;

class DispatcherBuilder
{
    /**
     * @var Url
     */
    private $baseUrl;

    /**
     * @var int
     */
    private $maxConcurrency;

    /**
     * @var bool
     */
    private $noDedupe = false;

    /**
     * @var bool
     */
    private $descendantsOnly;

    /**
     * @var string
     */
    private $publishTo;

    /**
     * @var bool
     */
    private $noPeerVerification = false;

    public function __construct(Url $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public static function create(string $url): self
    {
        $url = Url::fromUrl($url);

        return new self($url);
    }

    public function maxConcurrency(int $maxConcurrency): self
    {
        $this->maxConcurrency = $maxConcurrency;

        return $this;
    }

    public function noDeduplication(bool $value = true): self
    {
        $this->noDedupe = $value;

        return $this;
    }

    public function descendantsOnly(bool $value = true): self
    {
        $this->descendantsOnly = $value;

        return $this;
    }

    public function publishTo(string $outfile): self
    {
        $this->publishTo = $outfile;

        return $this;
    }

    public function noPeerVerification(bool $value): self
    {
        $this->noPeerVerification = $value;

        return $this;
    }

    public function build(): Dispatcher
    {
        $queue = $this->buildQueue();
        $queue->enqueue($this->baseUrl);

        return $this->buildDispatcher($queue);
    }

    private function buildDispatcher(UrlQueue $queue): Dispatcher
    {
        $publisher = new BlackholePublisher();

        if ($this->publishTo) {
            $resource = fopen($this->publishTo, 'w');

            if (false === $resource) {
                throw new RuntimeException(sprintf(
                    'Could not open file "%s"',
                    $this->publishTo
                ));
            }

            $stream = new ResourceOutputStream($resource);
            $publisher = new StreamPublisher($stream);
        }


        return new Dispatcher(
            $this->maxConcurrency,
            $publisher,
            new Crawler($this->buildClient()),
            $queue
        );
    }

    private function buildQueue(): UrlQueue
    {
        $queue = new RealUrlQueue();
        
        if (!$this->noDedupe) {
            $queue = new DedupeQueue($queue);
        }
        
        if ($this->descendantsOnly) {
            $queue = new OnlyDescendantOrSelfQueue($queue, $this->baseUrl);
        }
        return $queue;
    }

    private function buildClient(): Client
    {
        $cookieJar = new NullCookieJar;
        $tlsContext = new ClientTlsContext;
        $socketPool = new HttpSocketPool;

        if ($this->noPeerVerification) {
            $tlsContext = $tlsContext->withoutPeerVerification();
        }

        return new DefaultClient(
            $cookieJar,
            $socketPool,
            $tlsContext
        );
    }
}
