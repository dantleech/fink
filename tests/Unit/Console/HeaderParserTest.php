<?php

namespace DTL\Extension\Fink\Tests\Unit\Console;

use DTL\Extension\Fink\Console\Exception\CouldNotParseHeader;
use DTL\Extension\Fink\Console\HeaderParser;
use PHPUnit\Framework\TestCase;

class HeaderParserTest extends TestCase
{
    /**
     * @var HeaderParser
     */
    private $parser;

    public function setUp()
    {
        $this->parser = new HeaderParser();
    }

    /**
     * @dataProvider provideHeaderStrings
     */
    public function testParseHeaders(array $headerStrings, array $expected)
    {
        $this->assertEquals($expected, $this->parser->parseHeaders($headerStrings));
    }

    public function provideHeaderStrings()
    {
        yield [
            [],
            [],
        ];

        yield [
            [
                'Foo:Bar',
            ],
            [
                'Foo' => 'Bar',
            ],
        ];

        yield [
            [
                'Foo: Bar',
            ],
            [
                'Foo' => 'Bar',
            ],
        ];

        yield [
            [
                'Foo : Bar',
            ],
            [
                'Foo' => 'Bar',
            ],
        ];

        yield [
            [
                'Foo : Bar',
                'Barfoo: Bar',
            ],
            [
                'Foo' => 'Bar',
                'Barfoo' =>  'Bar',
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidValues
     */
    public function testExceptionOnInvalidValues(array $rawHeaders)
    {
        $this->expectException(CouldNotParseHeader::class);
        $this->parser->parseHeaders($rawHeaders);
    }

    public function provideInvalidValues()
    {
        yield [
            [
                ''
            ]
        ];

        yield [
            [
                'Foo'
            ]
        ];

        yield [
            [
                'Foo:bar',
                'aadasd',
            ]
        ];
    }
}
