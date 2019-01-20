<?php

namespace DTL\Extension\Fink\Tests\Unit\Model;

use DTL\Extension\Fink\Model\Exception\InvalidUrl;
use DTL\Extension\Fink\Model\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @dataProvider provideFromString
     */
    public function testFromString(string $url)
    {
        $this->assertEquals($url, Url::fromUrl($url));
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
        $url = Url::fromUrl($documentUrl);
        $result = $url->resolveUrl($linkUri);
        $this->assertEquals($expected, $result->__toString());
        $this->assertEquals($url, $result->referrer());
        $this->assertEquals(1, $result->distance());
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

        yield 'with port' => [
            'https://example.com:1234',
            'templates.html',
            'https://example.com:1234/templates.html'
        ];
    }

    public function testIsHttp()
    {
        $this->assertTrue(Url::fromUrl('https://foo.com')->isHttp());
        $this->assertTrue(Url::fromUrl('http://foo.com')->isHttp());
        $this->assertFalse(Url::fromUrl('ftp://foo.com')->isHttp());
    }

    /**
     * @dataProvider provideIsEqualToOrDescendant
     */
    public function testIsEqualToOrDescenant(string $baseUrl, string $url, bool $expected)
    {
        $this->assertEquals(
            $expected,
            Url::fromUrl($url)->equalsOrDescendantOf(Url::fromUrl($baseUrl))
        );
    }

    public function provideIsEqualToOrDescendant()
    {
        yield 'equal' => [
            'https://www.dantleech.com',
            'https://www.dantleech.com',
            true
        ];

        yield 'descendant' => [
            'https://www.dantleech.com',
            'https://www.dantleech.com/foo',
            true
        ];

        yield 'not descendant' => [
            'https://www.dantleech.com/foo',
            'https://www.dantleech.com',
            false
        ];
    }

    public function testResolveDistanceFromOriginalLink()
    {
        $baseLink = Url::fromUrl('http://www.dantleech.com');
        $this->assertEquals(0, $baseLink->distance());

        $result = $baseLink->resolveUrl('http://www.dantleech.com/1');
        $this->assertEquals(1, $result->distance());
        $result = $result->resolveUrl('http://www.dantleech.com/1');
        $this->assertEquals(2, $result->distance());
    }
}
