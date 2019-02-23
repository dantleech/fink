<?php

namespace DTL\Extension\Fink\Model;

use ArrayIterator;
use IteratorAggregate;

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
     * {@inheritDoc}
     */
    public function getIterator()
    {
        return new ArrayIterator($this->urls);
    }
}
