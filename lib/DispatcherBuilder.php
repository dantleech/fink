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
use DTL\Extension\Fink\Model\Limiter;
use DTL\Extension\Fink\Model\Limiter\ChainLimiter;
use DTL\Extension\Fink\Model\Limiter\ConcurrenyLimiter;
use DTL\Extension\Fink\Model\Limiter\RateLimiter;
use DTL\Extension\Fink\Model\Publisher\BlackholePublisher;
use DTL\Extension\Fink\Model\Publisher\CsvStreamPublisher;
use DTL\Extension\Fink\Model\Publisher\JsonStreamPublisher;
use DTL\Extension\Fink\Model\Publisher\YamlStreamPublisher;
use DTL\Extension\Fink\Model\Queue\DedupeQueue;
use DTL\Extension\Fink\Model\Queue\ExcludingQueue;
use DTL\Extension\Fink\Model\Queue\MaxDistanceQueue;
use DTL\Extension\Fink\Model\Queue\ExternalDistanceLimitingQueue;
use DTL\Extension\Fink\Model\Queue\RealUrlQueue;
use DTL\Extension\Fink\Model\UrlQueue;
use DTL\Extension\Fink\Model\Urls;
use RuntimeException;
use DTL\Extension\Fink\Model\Store\CircularReportStore;

class DispatcherBuilder
{
    public const PUBLISHER_CSV = 'csv';
    public const PUBLISHER_JSON = 'json';
    public const PUBLISHER_YAML = 'yaml';

    /**
     * @var int
     */
    private $maxConcurrency = 10;

    /**
     * @var bool
     */
    private $noDedupe = false;

    /**
     * @var int|null
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
     * @var int|null
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
     * @var array|null
     */
    private $excludeUrlPatterns;

    /**
     * @var array
     */
    private $headers = [
        'User-Agent' => 'Mozilla/5.0 (compatible; Artax; FinkPHP)'
    ];

    /**
     * @var Urls
     */
    private $baseUrls;

    /**
     * @var float
     */
    private $rateLimit;

    public function __construct(Urls $baseUrls)
    {
        $this->baseUrls = $baseUrls;
    }

    public static function create(array $urls): self
    {
        return new self(Urls::fromUrls($urls));
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
        foreach ($this->baseUrls as $baseUrl) {
            $queue->enqueue($baseUrl);
        }

        return $this->buildDispatcher($queue);
    }

    public function headers(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);

        return $this;
    }

    public function limitRate(float $rate): self
    {
        $this->rateLimit = $rate;

        return $this;
    }

    private function buildDispatcher(UrlQueue $queue): Dispatcher
    {
        return new Dispatcher(
            $this->buildPublisher(),
            new Crawler($this->buildClient()),
            $queue,
            new CircularReportStore($this->urlReportSize),
            $this->buildLimiter()
        );
    }

    private function buildQueue(): UrlQueue
    {
        $queue = new RealUrlQueue();
        
        if (!$this->noDedupe) {
            $queue = new DedupeQueue($queue);
        }

        if (null !== $this->limitExternalDistance) {
            $queue = new ExternalDistanceLimitingQueue($queue, $this->limitExternalDistance);
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
            Client::OP_DEFAULT_HEADERS => $this->headers,
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

            if ($this->publisherType === self::PUBLISHER_YAML) {
                return $this->buildYamlPublisher();
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

    private function buildYamlPublisher()
    {
        $resource = fopen($this->publishTo, 'w');

        if (false === $resource) {
            throw new RuntimeException(sprintf(
                'Could not open file "%s"',
                $this->publishTo
            ));
        }

        return new YamlStreamPublisher(new ResourceOutputStream($resource));
    }

    private function buildLimiter(): Limiter
    {
        $limiters = [
            new ConcurrenyLimiter($this->maxConcurrency)
        ];

        if ($this->rateLimit) {
            $limiters[] = new RateLimiter($this->rateLimit);
        }

        return new ChainLimiter($limiters);
    }
}
