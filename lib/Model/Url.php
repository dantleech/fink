<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\Model\Exception\InvalidUrl;
use League\Uri\Exception;
use League\Uri\Uri;

final class Url
{
    /**
     * @var Uri
     */
    private $uri;

    /**
     * @var ?Url
     */
    private $referrer;

    /**
     * @var int
     */
    private $distance;

    private function __construct(Uri $uri, Url $referrer = null, int $distance = 0)
    {
        $this->uri = $uri;
        $this->referrer = $referrer;
        $this->distance = $distance;
    }

    public static function fromUrl(string $url): self
    {
        try {
            $new = new self(Uri::createFromString($url));
        } catch (Exception $e) {
            throw new InvalidUrl($e->getMessage(), 0, $e);
        }

        return $new;
    }

    public function __toString(): string
    {
        return rtrim($this->uri->__toString(), '/');
    }

    public function resolveUrl($link): self
    {
        try {
            $link = Uri::createFromString($link);
        } catch (Exception $e) {
            throw new InvalidUrl($e->getMessage(), 0, $e);
        }

        if ($link->getPath()) {
            // prepend non-absolute paths with "/" to prevent them being
            // concatenated with the host, for example:
            // https://www.example.comtemplate.html
            $link = $link->withPath('/'.ltrim($link->getPath(), '/'));
        }

        if (!$link->getPath()) {
            $link = $link->withPath($this->uri->getPath());
        }

        if (!$link->getScheme()) {
            $link = $link->withScheme($this->uri->getScheme());
        }

        if (!$link->getHost()) {
            $link = $link->withHost($this->uri->getHost());
        }

        if (!$link->getQuery()) {
            $link = $link->withQuery($this->uri->getQuery());
        }

        if (!$link->getPort()) {
            $link = $link->withPort($this->uri->getPort());
        }

        return new self($link, $this, $this->distance + 1);
    }

    public function isHttp(): bool
    {
        return in_array($this->uri->getScheme(), ['http', 'https']);
    }

    public function equals(Url $url): bool
    {
        return $url->__toString() === $this->__toString();
    }

    public function equalsOrDescendantOf(Url $url): bool
    {
        return 0 === strpos($this->__toString(), $url->__toString());
    }

    public function referrer(): ?Url
    {
        return $this->referrer;
    }

    public function distance(): int
    {
        return $this->distance;
    }

    public function distanceIsGreaterThan(int $int): bool
    {
        return $this->distance > $int;
    }
}
