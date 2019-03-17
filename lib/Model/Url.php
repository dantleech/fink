<?php

namespace DTL\Extension\Fink\Model;

use DTL\Extension\Fink\Model\Exception\InvalidUrl;
use DTL\Extension\Fink\Model\Exception\InvalidUrlComparison;
use League\Uri\AbstractUri;
use League\Uri\Exception;
use League\Uri\Uri;
use Webmozart\PathUtil\Path;

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

    /**
     * @var ReferringElement|null
     */
    private $referringElement;

    private function __construct(Uri $uri, Url $referrer = null, int $distance = 0, ReferringElement $referringElement = null)
    {
        $this->uri = $uri;
        $this->referrer = $referrer;
        $this->distance = $distance;
        $this->referringElement = $referringElement;
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

    public function resolveUrl(string $link, ReferringElement $referringElement = null): self
    {
        try {
            $link = Uri::createFromString($link);
        } catch (Exception $e) {
            throw new InvalidUrl($e->getMessage(), 0, $e);
        }

        if ($link->getPath()) {
            $link = $link->withPath($this->normalizePath($link));
        }

        if (!$link->getScheme()) {
            $link = $link->withScheme($this->uri->getScheme());
        }

        if (!$link->getHost()) {
            $link = $link->withHost($this->uri->getHost());
        }

        if (!$link->getPort()) {
            $link = $link->withPort($this->uri->getPort());
        }

        if ($link->getFragment()) {
            // unconditionally remove fragments
            $link = $link->withFragment('');
        }

        return new self($link, $this, $this->distance + 1, $referringElement);
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

    public function externalDistanceTo(Url $url): int
    {
        $distance = 0;

        if ($url->equalsOrDescendantOf($this)) {
            return $distance;
        }

        while ($referrer = $url->referrer()) {
            if ($url->equalsOrDescendantOf($this)) {
                return $distance;
            }

            $distance++;

            if ($referrer === $this) {
                return $distance;
            }

            $url = $referrer;
        }

        throw new InvalidUrlComparison(sprintf(
            'URL "%s" was not a linked descendant of the base URL "%s"',
            $url->__toString(),
            $this->__toString()
        ));
    }

    public function originUrl(): Url
    {
        if (null === $this->referrer) {
            return $this;
        }

        return $this->referrer->originUrl();
    }

    private function normalizePath(AbstractUri $link): string
    {
        $path = $link->getPath();

        if ($this->uri->getPath()) {
            $path = Path::makeAbsolute($path, Path::getDirectory($this->uri->getPath()));
        }

        // prepend non-absolute paths with "/" to prevent them being
        // concatenated with the host, for example:
        // https://www.example.comtemplate.html
        return '/'.ltrim($path, '/');
    }

    public function referringElement(): ?ReferringElement
    {
        return $this->referringElement;
    }
}
