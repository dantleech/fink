<?php

namespace DTL\Extension\Fink\Adapter\Artax;

use Amp\Http\Client\Cookie\CookieJar;
use Amp\Http\Cookie\ResponseCookie;
use Amp\Promise;
use Amp\Success;
use Psr\Http\Message\UriInterface as PsrUri;

class ImmutableCookieJar implements CookieJar
{
    /**
     * @var CookieJar
     */
    private $innerJar;

    public function __construct(CookieJar $innerJar)
    {
        $this->innerJar = $innerJar;
    }

    public function get(PsrUri $uri): Promise
    {
        return $this->innerJar->get($uri);
    }

    public function store(ResponseCookie ...$cookies): Promise
    {
        return new Success; // nothing else to do
    }
}
