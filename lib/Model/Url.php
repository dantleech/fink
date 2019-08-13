<?php

namespace DTL\Extension\Fink\Model;

use Amp\Http\Client\Interceptor\FollowRedirects;
use DTL\Extension\Fink\Model\Exception\InvalidUrl;
use DTL\Extension\Fink\Model\Exception\InvalidUrlComparison;
use League\Uri\Exception;
use League\Uri\Http as HttpUri;
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

    /**
     * @var ReferringElement|null
     */
    private $referringElement;

    private function __construct(HttpUri $uri, Url $referrer = null, int $distance = 0, ReferringElement $referringElement = null)
    {
        $this->uri = $uri;
        $this->referrer = $referrer;
        $this->distance = $distance;
        $this->referringElement = $referringElement;
    }

    public static function fromUrl(string $url): self
    {
        try {
            $new = new self(HttpUri::createFromString($url));
        } catch (Exception $e) {
            throw new InvalidUrl($e->getMessage(), 0, $e);
        }

        return $new;
    }

    public function __toString(): string
    {
        return (string) $this->uri;
    }

    public function resolveUrl(string $link, ReferringElement $referringElement = null): self
    {
        try {
            $parsedLink = HttpUri::createFromString($link);
        } catch (Exception $e) {
            throw new InvalidUrl($e->getMessage(), 0, $e);
        }

        $resolvedLink = FollowRedirects::resolve($this->uri, $parsedLink);

        if ($resolvedLink->getFragment()) {
            // unconditionally remove fragments
            $resolvedLink = $resolvedLink->withFragment('');
        }

        return new self(HttpUri::createFromString((string) $resolvedLink), $this, $this->distance + 1, $referringElement);
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

    public function referringElement(): ?ReferringElement
    {
        return $this->referringElement;
    }
}
