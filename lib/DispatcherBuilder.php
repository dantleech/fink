<?php

namespace DTL\Extension\Fink;

use Amp\Artax\Client;
use Amp\Artax\Cookie\NullCookieJar;
use Amp\Artax\DefaultClient;
use Amp\Artax\HttpSocketPool;
use Amp\ByteStream\ResourceOutputStream;
use Amp\Socket\ClientTlsContext;
use DTL\Extension\Fink\Adapter\Artax\ImmutableCookieJar;
use DTL\Extension\Fink\Adapter\Artax\NetscapeCookieFileJar;
use DTL\Extension\Fink\Model\Crawler;
use DTL\Extension\Fink\Model\Dispatcher;
use DTL\Extension\Fink\Model\Publisher\BlackholePublisher;
use DTL\Extension\Fink\Model\Publisher\CsvStreamPublisher;
use DTL\Extension\Fink\Model\Publisher\JsonStreamPublisher;
use DTL\Extension\Fink\Model\Queue\DedupeQueue;
use DTL\Extension\Fink\Model\Queue\ExcludingQueue;
use DTL\Extension\Fink\Model\Queue\MaxDistanceQueue;
use DTL\Extension\Fink\Model\Queue\ExternalDistanceLimitingQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlQueue;
use RuntimeException;
use DTL\Extension\Fink\Model\Store\CircularReportStore;

class DispatcherBuilder
{
    public const PUBLISHER_CSV = 'csv';
    public const PUBLISHER_JSON = 'json';

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
     * @var int
     */
    private $limitExternalDistance = null;

    /**
     * @var string
     */
    private $publishTo;

    /**
     * @var bool
     */
    private $noPeerVerification = false;

    /**
     * @var int
     */
    private $maxDistance = null;

    /**
     * @var string
     */
    private $loadCookies;

    /**
     * @var int
     */
    private $urlReportSize = 5;

    /**
     * @var string
     */
    private $publisherType = self::PUBLISHER_JSON;

    /**
     * @var int
     */
    private $clientTransferTimeout = 15000;

    /**
     * @var int
     */
    private $clientMaxRedirects = 5;

    /**
     * @var array
     */
    private $excludeUrlPatterns;

    public function __construct(Url $baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public static function create(string $url): self
    {
        $url = Url::fromUrl($url);

        return new self($url);
    }

    public function excludeUrlPatterns(array $urlPatterns): self
    {
        $this->excludeUrlPatterns = $urlPatterns;

        return $this;
    }

    public function publisher(string $type): self
    {
        $this->publisherType = $type;
        return $this;
    }

    public function maxConcurrency(int $maxConcurrency): self
    {
        $this->maxConcurrency = $maxConcurrency;

        return $this;
    }

    public function maxDistance(int $maxDistance): self
    {
        $this->maxDistance = $maxDistance;

        return $this;
    }

    public function noDeduplication(bool $value = true): self
    {
        $this->noDedupe = $value;

        return $this;
    }

    public function limitExternalDistance(int $distance): self
    {
        $this->limitExternalDistance = $distance;

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

    public function loadCookies(string $file): self
    {
        $this->loadCookies = $file;

        return $this;
    }

    public function clientTransferTimeout(int $milliseconds)
    {
        $this->clientTransferTimeout = $milliseconds;

        return $this;
    }

    public function clientMaxRedirects(int $maxRedirects)
    {
        $this->clientMaxRedirects = $maxRedirects;

        return $this;
    }

    public function urlReportSize(int $size): self
    {
        $this->urlReportSize = $size;

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
        return new Dispatcher(
            $this->maxConcurrency,
            $this->buildPublisher(),
            new Crawler($this->buildClient()),
            $queue,
            new CircularReportStore($this->urlReportSize)
        );
    }

    private function buildQueue(): UrlQueue
    {
        $queue = new RealUrlQueue();
        
        if (!$this->noDedupe) {
            $queue = new DedupeQueue($queue);
        }

        if (null !== $this->limitExternalDistance) {
            $queue = new ExternalDistanceLimitingQueue($queue, $this->baseUrl, $this->limitExternalDistance);
        }

        if (null !== $this->maxDistance) {
            $queue = new MaxDistanceQueue($queue, $this->maxDistance);
        }

        if (null !== $this->excludeUrlPatterns) {
            $queue = new ExcludingQueue($queue, $this->excludeUrlPatterns);
        }

        return $queue;
    }

    private function buildClient(): Client
    {
        $cookieJar = new NullCookieJar;
        $tlsContext = new ClientTlsContext;
        $socketPool = new HttpSocketPool;

        if ($this->loadCookies) {
            if (!file_exists($this->loadCookies)) {
                throw new RuntimeException(sprintf(
                    'Cookie file "%s" does not exist',
                    $this->loadCookies
                ));
            }

            $cookieJar = new ImmutableCookieJar(
                new NetscapeCookieFileJar($this->loadCookies)
            );
        }

        if ($this->noPeerVerification) {
            $tlsContext = $tlsContext->withoutPeerVerification();
        }

        $client = new DefaultClient(
            $cookieJar,
            $socketPool,
            $tlsContext
        );

        $client->setOptions([
            Client::OP_TRANSFER_TIMEOUT => $this->clientTransferTimeout,
            Client::OP_MAX_REDIRECTS => $this->clientMaxRedirects,
        ]);

        return $client;
    }

    private function buildPublisher()
    {
        if ($this->publishTo) {
            if ($this->publisherType === self::PUBLISHER_JSON) {
                return $this->buildJsonPublisher();
            }

            if ($this->publisherType === self::PUBLISHER_CSV) {
                return new CsvStreamPublisher($this->publishTo, true);
            }

            throw new RuntimeException(sprintf(
                'Unknown publisher type "%s" must be one of "%s"',
                $this->publisherType,
                implode('", "', [ self::PUBLISHER_JSON, self::PUBLISHER_CSV ])
            ));
        }
            
        return new BlackholePublisher();
    }

    private function buildJsonPublisher()
    {
        $resource = fopen($this->publishTo, 'w');
        
        if (false === $resource) {
            throw new RuntimeException(sprintf(
                'Could not open file "%s"',
                $this->publishTo
            ));
        }
        
        return new JsonStreamPublisher(new ResourceOutputStream($resource));
    }
}
