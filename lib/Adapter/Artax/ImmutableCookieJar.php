<?php

namespace DTL\Extension\Fink\Adapter\Artax;

use Amp\Http\Client\Cookie\CookieJar;
use Amp\Http\Client\Request;
use Amp\Http\Cookie\ResponseCookie;

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

    public function get(Request $request): array
    {
        return $this->innerJar->get($request);
    }

    public function getAll(): array
    {
        return $this->innerJar->getAll();
    }

    public function store(ResponseCookie $cookie): void
    {
        // nothing
    }

    public function remove(ResponseCookie $cookie): void
    {
        // nothing
    }

    public function removeAll(): void
    {
        // nothing
    }
}
