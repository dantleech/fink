<?php

namespace DTL\Extension\Fink\Model;

use League\Uri\Uri;

final class Url
{
    /**
     * @var Uri
     */
    private $uri;

    public function __construct(Uri $uri)
    {
        $this->uri = $uri;
    }

    public static function fromUrl(string $url): self
    {
        return new self(Uri::createFromString($url));
    }

    public function __toString(): string
    {
        return rtrim($this->uri->__toString(), '/');
    }

    public function resolveUrl($link): self
    {
        $link = Uri::createFromString($link);

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

        return new self($link);
    }

    public function isHttp(): bool
    {
        return in_array($this->uri->getScheme(), ['http', 'https']);
    }
}
