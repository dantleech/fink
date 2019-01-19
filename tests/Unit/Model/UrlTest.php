<?php

namespace DTL\Extension\Fink\Tests\Unit\Model;

use DTL\Extension\Fink\Model\Exception\InvalidUrl;
use DTL\Extension\Fink\Model\Url;
use DTL\Extension\Fink\Model\UrlFactory;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @dataProvider provideFromString
     */
    public function testFromString(string $url)
    {
        $this->assertEquals($url, UrlFactory::fromUrl($url));
    }

    public function provideFromString()
    {
        yield [
            'https:://example.com'
        ];

        yield [
            'example.com'
        ];
    }

    /**
     * @dataProvider provideInvalidUrl
     */
    public function testInvalidUrl(string $invalid)
    {
        $this->expectException(InvalidUrl::class);
        Url::fromUrl($invalid);
    }

    public function provideInvalidUrl()
    {
        yield [ "https://twitter.com/intent/favorite?tweet_id='+
tids[n]+" ];
    }

    /**
     * @dataProvider provideResolve
     */
    public function testResolve(string $documentUrl, string $linkUri, string $expected)
    {
        $url = UrlFactory::fromUrl($documentUrl);
        $result = $url->resolveUrl($linkUri);
        $this->assertEquals($expected, $result->__toString());
    }

    public function provideResolve()
    {
        yield [
            'https://example.com',
            'https://example.com',
            'https://example.com'
        ];

        yield [
            'https://example.com',
            '/foo',
            'https://example.com/foo'
        ];

        yield [
            'https://example.com',
            '/foo?foo',
            'https://example.com/foo?foo'
        ];

        yield [
            'https://example.com',
            '?foo',
            'https://example.com?foo'
        ];

        yield [
            'https://example.com',
            '?foo&bar',
            'https://example.com?foo&bar'
        ];

        yield 'path only' => [
            'https://example.com',
            'templates.html',
            'https://example.com/templates.html'
        ];
    }

    public function testIsHttp()
    {
        $this->assertTrue(UrlFactory::fromUrl('https://foo.com')->isHttp());
        $this->assertTrue(UrlFactory::fromUrl('http://foo.com')->isHttp());
        $this->assertFalse(UrlFactory::fromUrl('ftp://foo.com')->isHttp());
    }
}
