<?php

namespace DTL\Extension\Fink\Tests\Unit\Model;

use DTL\Extension\Fink\Model\Exception\InvalidUrl;
use DTL\Extension\Fink\Model\Exception\InvalidUrlComparison;
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

        yield 'ignores fragments' => [
            'https://example.com',
            '#foobar',
            'https://example.com'
        ];

        yield 'link to host only from path' => [
            'https://github.com/phpactor/behat-extension',
            'https://training.github.com',
            'https://training.github.com'
        ];

        yield 'does not include document parameters' => [
            'https://github.com/phpactor/behat-extension?foo=bar',
            'https://training.github.com',
            'https://training.github.com'
        ];

        yield 'makes relative URLs absolute' => [
            'https://example.com/info/faq',
            '../../info/app',
            'https://example.com/info/app'
        ];

        yield 'ignores extra ".." segments' => [
            'https://example.com/info/faq',
            '../../../../../baz',
            'https://example.com/baz'
        ];

        yield 'uses current base path for relative names' => [
            'https://example.com/info/faq.html',
            'baz.html',
            'https://example.com/info/baz.html'
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

    /**
     * @dataProvider provideExternalDistanceFromUrl
     */
    public function testReturnsExternalDistanceFromUrl(array $urlChain, int $expectedDistance)
    {
        $baseUrl = array_shift($urlChain);
        $baseUrl = Url::fromUrl($baseUrl);
        assert($baseUrl instanceof Url);
        $previous = $baseUrl;

        foreach ($urlChain as $url) {
            $previous = $previous->resolveUrl($url);
        }

        $this->assertEquals($expectedDistance, $baseUrl->externalDistanceTo($previous));
    }

    public function provideExternalDistanceFromUrl()
    {
        yield 'base URL has zero distance' => [
            [
                'https://www.dantleech.com',
            ],
            0
        ];

        yield 'descendant of base url' => [
            [
                'https://www.dantleech.com',
                'https://www.dantleech.com/foobar',
            ],
            0
        ];

        yield 'descendant of descendant of base URL' => [
            [
                'https://www.dantleech.com',
                'https://www.dantleech.com/foobar',
                'https://www.dantleech.com/foobar/barfoo',
            ],
            0
        ];

        yield 'external URL' => [
            [
                'https://www.dantleech.com',
                'https://www.example.com',
            ],
            1
        ];

        yield 'external URL two steps removed' => [
            [
                'https://www.dantleech.com',
                'https://www.example.com',
                'https://www.example.com/foo',
            ],
            2
        ];

        yield 'two internal URLs then an external' => [
            [
                'https://www.dantleech.com',
                'https://www.dantleech.com/foo',
                'https://www.example.com',
            ],
            1
        ];

        yield 'three levels removed' => [
            [
                'https://www.dantleech.com',
                'https://www.dantleech.com/foo',
                'https://www.example.com',
                'https://www.example.com/foobar',
                'https://www.example.com/foobar/bar',
            ],
            3
        ];

        yield 'intermediate base URL' => [
            [
                'https://www.dantleech.com',
                'https://www.dantleech.com/foo',
                'https://www.example.com',
                'https://www.dantleech.com',
                'https://www.example.com',
            ],
            1
        ];

        yield 'intermediate descendant of base URL' => [
            [
                'https://www.dantleech.com',
                'https://www.dantleech.com/foo',
                'https://www.example.com',
                'https://www.dantleech.com/bar/foo',
                'https://www.example.com',
            ],
            1
        ];
    }

    public function testExceptionWhenTryingToDetermineExternalDistanceFromDisjointedUrls()
    {
        $this->expectException(InvalidUrlComparison::class);
        $url1 = Url::fromUrl('https://www.example1.com');
        $url2 = Url::fromUrl('https://www.example2.com');
        $url1->externalDistanceTo($url2);
    }

    public function testReturnsBaseUrl()
    {
        $url1 = Url::fromUrl('https://www.example1.com');
        $url = $url1->resolveUrl('https://foobar.com');
        $url = $url->resolveUrl('https://barfoo.com');
        $url = $url->resolveUrl('https://yzed.com');

        $this->assertSame($url1, $url->originUrl());
    }
}
