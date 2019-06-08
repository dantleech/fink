<?php

namespace DTL\Extension\Fink\Console\Command;

use Amp\Loop;
use DTL\Extension\Fink\Console\DisplayBuilder;
use DTL\Extension\Fink\Console\HeaderParser;
use DTL\Extension\Fink\Model\DispatcherBuilderFactory;
use DTL\Extension\Fink\Model\Dispatcher;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use DTL\Extension\Fink\Console\Command\Exception\AtLeastOneFailure;

class CrawlCommand extends Command
{
    public const EXIT_STATUS_SIGINT = 130; // 128 + SIGINT
    public const EXIT_STATUS_FAILURE = 4;
    public const EXIT_STATUS_SUCCESS = 0;

    private const DISPLAY_POLL_TIME = 100;

    private const ARG_URLS = 'url';

    private const OPT_CONCURRENCY = 'concurrency';
    private const OPT_EXT_DISTANCE = 'max-external-distance';
    private const OPT_MAX_DISTANCE = 'max-distance';
    private const OPT_NO_DEDUPE = 'no-dedupe';
    private const OPT_OUTPUT = 'output';
    private const OPT_INSECURE = 'insecure';
    private const OPT_LOAD_COOKIES = 'load-cookies';
    private const OPT_REQUEST_INTERVAL = 'interval';
    private const OPT_PUBLISHER = 'publisher';
    private const OPT_DISPLAY_BUFSIZE = 'display-bufsize';
    private const OPT_CLIENT_MAX_TIMEOUT = 'client-timeout';
    private const OPT_CLIENT_MAX_REDIRECTS = 'client-redirects';
    private const OPT_EXCLUDE_URL = 'exclude-url';
    private const OPT_HEADER = 'header';
    private const OPT_RATE = 'rate';
    private const OPT_INCLUDE_LINK = 'include-link';
    private const OPT_CLIENT_MAX_HEADER_SIZE = 'client-max-header-size';
    private const OPT_CLIENT_MAX_BODY_SIZE = 'client-max-body-size';
    private const OPT_CLIENT_SECURITY_LEVEL = 'client-security-level';
    private const OPT_DISPLAY = 'display';
    private const OPT_STDOUT = 'stdout';

    /**
     * @var DispatcherBuilderFactory
     */
    private $factory;

    /**
     * @var DisplayBuilder
     */
    private $displayBuilder;

    /**
     * @var int
     */
    private $exitCode = self::EXIT_STATUS_SUCCESS;

    /**
     * @var bool
     */
    private $shuttingDown = false;

    /**
     * @var HeaderParser
     */
    private $headerParser;

    public function __construct(DispatcherBuilderFactory $factory, DisplayBuilder $displayBuilder)
    {
        parent::__construct();
        $this->factory = $factory;
        $this->displayBuilder = $displayBuilder;
        $this->headerParser = new HeaderParser();
    }

    protected function configure()
    {
        $this->setDescription('Crawl the given URL');

        $this->addArgument(self::ARG_URLS, InputArgument::REQUIRED|InputArgument::IS_ARRAY, 'URL(s) to crawl');

        $this->addOption(self::OPT_CONCURRENCY, 'c', InputOption::VALUE_REQUIRED, 'Concurrency', 10);
        $this->addOption(self::OPT_OUTPUT, 'o', InputOption::VALUE_REQUIRED, 'Output file');
        $this->addOption(self::OPT_NO_DEDUPE, 'D', InputOption::VALUE_NONE, 'Do not de-duplicate URLs');
        $this->addOption(self::OPT_EXT_DISTANCE, 'x', InputOption::VALUE_REQUIRED, 'Limit the external (disjoint) distance from the base URL', 1);
        $this->addOption(self::OPT_INSECURE, 'k', InputOption::VALUE_NONE, 'Allow insecure server connections with SSL');
        $this->addOption(self::OPT_MAX_DISTANCE, 'm', InputOption::VALUE_REQUIRED, 'Maximum link distance from base URL');
        $this->addOption(self::OPT_LOAD_COOKIES, null, InputOption::VALUE_REQUIRED, 'Load cookies from file');
        $this->addOption(self::OPT_REQUEST_INTERVAL, null, InputOption::VALUE_REQUIRED, 'Dispatch request every n milliseconds', 10);
        $this->addOption(self::OPT_PUBLISHER, 'p', InputOption::VALUE_REQUIRED, 'Publisher to use: `json` or `csv`', 'json');
        $this->addOption(self::OPT_DISPLAY_BUFSIZE, null, InputOption::VALUE_REQUIRED, 'Size of report buffer to display', 5);
        $this->addOption(self::OPT_CLIENT_MAX_BODY_SIZE, null, InputOption::VALUE_REQUIRED, 'Max body size for HTTP client (in bytes)', 10485760);
        $this->addOption(self::OPT_CLIENT_MAX_HEADER_SIZE, null, InputOption::VALUE_REQUIRED, 'Max header size for HTTP client (in bytes)', 8192);
        $this->addOption(self::OPT_CLIENT_MAX_REDIRECTS, null, InputOption::VALUE_REQUIRED, 'Maximum number of redirects to follow', 5);
        $this->addOption(self::OPT_CLIENT_MAX_TIMEOUT, null, InputOption::VALUE_REQUIRED, 'Number of milliseconds to wait for URL', 15000);
        $this->addOption(self::OPT_CLIENT_SECURITY_LEVEL, null, InputOption::VALUE_REQUIRED, 'TLS Security level, see https://www.openssl.org/docs/manmaster/man3/SSL_CTX_set_security_level.html');
        $this->addOption(self::OPT_EXCLUDE_URL, null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Exclude PCRE URL pattern', []);
        $this->addOption(self::OPT_HEADER, null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Custom header, e.g. "X-Teapot: Me"', []);
        $this->addOption(self::OPT_RATE, null, InputOption::VALUE_REQUIRED, 'Set max request rate (as requests per second)', []);
        $this->addOption(self::OPT_INCLUDE_LINK, null, InputOption::VALUE_REQUIRED|InputOption::VALUE_IS_ARRAY, 'Add an additional URL to the set of URLs under the base URL', []);
        $this->addOption(self::OPT_DISPLAY, 'd', InputOption::VALUE_REQUIRED, 'Display specification, e.g. +memory', '');
        $this->addOption(self::OPT_STDOUT, null, InputOption::VALUE_NONE, 'Stream directly to stdout (disable realtime display and out file)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        assert($output instanceof ConsoleOutput);

        $dispatcher = $this->buildDispatcher($input);
        $dispatcher->dispatch();

        $section1 = $output->section();

        $display = $this->displayBuilder->build($this->castToString($input->getOption(self::OPT_DISPLAY)));

        Loop::repeat($this->castToInt($input->getOption(self::OPT_REQUEST_INTERVAL)), function () use ($dispatcher) {
            $dispatcher->dispatch();
        });

        Loop::repeat(self::DISPLAY_POLL_TIME, function () use ($display, $section1, $dispatcher, $input) {
            if (false === $input->getOption(self::OPT_STDOUT) && false === $this->shuttingDown) {
                $section1->overwrite($display->render($section1->getFormatter(), $dispatcher->status()));
            }

            if ($dispatcher->status()->nbConcurrentRequests === 0 && $dispatcher->status()->queueSize === 0) {
                Loop::stop();

                if ($dispatcher->status()->nbFailures) {
                    throw new AtLeastOneFailure();
                }
            }
        });

        if (extension_loaded('pcntl')) {
            Loop::onSignal(SIGINT, function () use ($output) {
                $this->exitCode = self::EXIT_STATUS_SIGINT;
                $this->shuttingDown = true;
                $output->write('SIGINT received');
                Loop::stop();
            });
        }

        try {
            Loop::run();
        } catch (AtLeastOneFailure $e) {
            return self::EXIT_STATUS_FAILURE;
        }

        return $this->exitCode;
    }

    private function buildDispatcher(InputInterface $input): Dispatcher
    {
        $urls = $this->castToArray($input->getArgument(self::ARG_URLS));

        $maxConcurrency = $this->castToInt($input->getOption(self::OPT_CONCURRENCY));
        $outfile = $input->getOption(self::OPT_OUTPUT);
        $noDedupe = $this->castToBool($input->getOption(self::OPT_NO_DEDUPE));
        $publisher = $this->castToString($input->getOption(self::OPT_PUBLISHER));
        $externalDistance = $input->getOption(self::OPT_EXT_DISTANCE);
        $insecure = $this->castToBool($input->getOption(self::OPT_INSECURE));
        $maxDistance = $input->getOption(self::OPT_MAX_DISTANCE);
        $cookieFile = $input->getOption(self::OPT_LOAD_COOKIES);
        $bufSize = $this->castToInt($input->getOption(self::OPT_DISPLAY_BUFSIZE));
        $maxRedirects = $this->castToInt($input->getOption(self::OPT_CLIENT_MAX_REDIRECTS));
        $maxTimeout = $this->castToInt($input->getOption(self::OPT_CLIENT_MAX_TIMEOUT));
        $excludeUrls = $this->castToArray($input->getOption(self::OPT_EXCLUDE_URL));
        $headers = $this->castToArray($input->getOption(self::OPT_HEADER));
        $rate = $input->getOption(self::OPT_RATE);
        $includeLinks = $this->castToArray($input->getOption(self::OPT_INCLUDE_LINK));
        $maxBodySize = $this->castToInt($input->getOption(self::OPT_CLIENT_MAX_BODY_SIZE));
        $maxHeaderSize = $this->castToInt($input->getOption(self::OPT_CLIENT_MAX_HEADER_SIZE));
        $sslSecurityLevel = $input->getOption(self::OPT_CLIENT_SECURITY_LEVEL);

        $builder = $this->factory->createForUrls($urls);
        $builder->maxConcurrency($maxConcurrency);
        $builder->noDeduplication($noDedupe);
        $builder->publisher($publisher);
        $builder->urlReportSize($bufSize);
        $builder->clientMaxRedirects($maxRedirects);
        $builder->clientTransferTimeout($maxTimeout);
        $builder->headers($this->headerParser->parseHeaders($headers));
        $builder->clientMaxBodySize($maxBodySize);
        $builder->clientMaxHeaderSize($maxHeaderSize);

        if ($sslSecurityLevel) {
            $builder->clientSecurityLevel($this->castToInt($sslSecurityLevel));
        }

        if (null !== $rate) {
            $builder->limitRate($this->castToFloat($rate));
        }

        if (null !== $externalDistance) {
            $builder->limitExternalDistance($this->castToInt($externalDistance));
        }

        $builder->noPeerVerification($insecure);

        if ($outfile) {
            $builder->publishTo($this->castToString($outfile));
        }

        if ($input->getOption(self::OPT_STDOUT)) {
            $builder->publishResource(STDOUT);
        }

        if ($cookieFile) {
            $builder->loadCookies($this->castToString($cookieFile));
        }

        if (null !== $maxDistance) {
            $builder->maxDistance($this->castToInt($maxDistance));
        }

        if (!empty($excludeUrls)) {
            $builder->excludeUrlPatterns($excludeUrls);
        }

        if (!empty($includeLinks)) {
            $builder->includeLinks($includeLinks);
        }

        return $builder->build();
    }

    private function castToInt($value): int
    {
        if (!is_numeric($value)) {
            throw new RuntimeException(sprintf(
                'value was not an int, got "%s"',
                var_export($value, true)
            ));
        }

        return (int) $value;
    }

    private function castToString($value): string
    {
        if (!is_string($value)) {
            throw new RuntimeException(sprintf(
                'value was not a string'
            ));
        }

        return $value;
    }

    private function castToBool($value): bool
    {
        if (!is_bool($value)) {
            throw new RuntimeException(sprintf(
                'value was not a bool'
            ));
        }

        return $value;
    }

    private function castToArray($value): array
    {
        return (array) $value;
    }

    private function castToFloat($value): float
    {
        return (float) $value;
    }
}
