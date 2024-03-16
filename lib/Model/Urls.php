<?php

namespace DTL\Extension\Fink\Model;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

class Urls implements IteratorAggregate
{
    /**
     * @var Url[]
     */
    private $urls;

    public function __construct(array $urls)
    {
        foreach ($urls as $url) {
            $this->add($url);
        }
    }

    public static function fromUrls(array $urls)
    {
        return new self(array_map(function (string $url) {
            return Url::fromUrl($url);
        }, $urls));
    }

    private function add(Url $url)
    {
        $this->urls[] = $url;
    }

    /**
     * @return Traversable<int,Url>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->urls);
    }
}
