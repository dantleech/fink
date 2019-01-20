<?php

namespace DTL\Extension\Fink\Adapter\Artax;

use Amp\Artax\Cookie\Cookie;
use Amp\Artax\Cookie\CookieJar;

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

    public function get(string $domain, string $path = '', string $name = null): array
    {
        return $this->innerJar->get($domain, $path, $name);
    }

    public function getAll(): array
    {
        return $this->innerJar->getAll();
    }

    public function store(Cookie $cookie)
    {
        // nothing
    }

    public function remove(Cookie $cookie)
    {
        // nothing
    }

    public function removeAll()
    {
        // nothing
    }
}
