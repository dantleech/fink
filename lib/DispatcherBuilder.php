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
use DTL\Extension\Fink\Model\Limiter\ConcurrencyLimiter;
use DTL\Extension\Fink\Model\Limiter\RateLimiter;
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
use DTL\Extension\Fink\Model\Urls;
use RuntimeException;
use DTL\Extension\Fink\Model\Store\CircularReportStore;

class DispatcherBuilder
{
    public const PUBLISHER_CSV = 'csv';
    public const PUBLISHER_JSON = 'json';

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
     * @var Urls<Url>
     */
    private $baseUrls;

    /**
     * @var float
     */
    private $rateLimit;

    /**
     * @var string[]
     */
    private $includeLinks = [];

    /**
     * @var int
     */
    private $clientMaxHeaderSize;

    /**
     * @var int
     */
    private $clientMaxBodySize;

    /**
     * @var int
     */
    private $clientSslSecurityLevel;

    /**
     * @var resource|null
     */
    private $publishToResource;

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

    public function publishResource($resource): self
    {
        $this->publishToResource = $resource;

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

    public function clientTransferTimeout(int $milliseconds): self
    {
        $this->clientTransferTimeout = $milliseconds;

        return $this;
    }

    public function clientMaxRedirects(int $maxRedirects): self
    {
        $this->clientMaxRedirects = $maxRedirects;

        return $this;
    }

    public function clientSecurityLevel(int $sslSecurityLevel)
    {
        $this->clientSslSecurityLevel = $sslSecurityLevel;

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
            foreach ($this->includeLinks as $additionalUrl) {
                $queue->enqueue($baseUrl->resolveUrl($additionalUrl));
            }
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

    public function includeLinks(array $includeLinks): self
    {
        $this->includeLinks = $includeLinks;

        return $this;
    }

    public function clientMaxHeaderSize(int $maxHeaderSize): self
    {
        $this->clientMaxHeaderSize = $maxHeaderSize;

        return $this;
    }

    public function clientMaxBodySize(int $maxBodySize): self
    {
        $this->clientMaxBodySize = $maxBodySize;

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

        // set the default secutity level if PHP is compiled with support for it
        if (\OPENSSL_VERSION_NUMBER >= 0x10100000) {
            $tlsContext = $tlsContext->withSecurityLevel(1);
        }

        if ($this->clientSslSecurityLevel) {
            $tlsContext = $tlsContext->withSecurityLevel($this->clientSslSecurityLevel);
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
            Client::OP_MAX_BODY_BYTES => $this->clientMaxBodySize,
            Client::OP_MAX_HEADER_BYTES => $this->clientMaxHeaderSize,
        ]);

        return $client;
    }

    private function buildPublisher()
    {
        if ($this->publishTo || $this->publishToResource) {
            if ($this->publisherType === self::PUBLISHER_JSON) {
                return $this->buildJsonPublisher();
            }

            if ($this->publisherType === self::PUBLISHER_CSV) {
                return new CsvStreamPublisher($this->buildPublishStream(), true);
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
        return new JsonStreamPublisher(new ResourceOutputStream($this->buildPublishStream()));
    }

    private function buildLimiter(): Limiter
    {
        $limiters = [
            new ConcurrencyLimiter($this->maxConcurrency)
        ];

        if ($this->rateLimit) {
            $limiters[] = new RateLimiter($this->rateLimit);
        }

        return new ChainLimiter($limiters);
    }

    private function buildPublishStream()
    {
        if ($this->publishToResource) {
            return $this->publishToResource;
        }

        $resource = fopen($this->publishTo, 'w');
        
        if (false === $resource) {
            throw new RuntimeException(sprintf(
                'Could not open file "%s"',
                $this->publishTo
            ));
        }

        return $resource;
    }
}
