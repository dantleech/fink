<?php

namespace DTL\Extension\Fink\Model;

class UrlFactory
{
    public static function fromUrl(string $url): Url
    {
        return Url::fromUrl($url);
    }
}
