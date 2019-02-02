<?php

namespace DTL\Extension\Fink\Tests\Unit\Model;

use DTL\Extension\Fink\Model\HttpStatusCode;
use PHPUnit\Framework\TestCase;

class HttpStatusCodeTest extends TestCase
{
    /**
     * @dataProvider provideSuccess
     */
    public function testIsSuccess(int $code, bool $expected)
    {
        $code = HttpStatusCode::fromInt($code);
        $this->assertEquals($expected, $code->isSuccess());
    }

    public function provideSuccess()
    {
        yield [ '301', false ];
        yield [ '300', false ];
        yield [ '250', true ];
        yield [ '200', true ];
        yield [ '199', false ];
    }

    /**
     * @dataProvider provideRedirect
     */
    public function testIsRedirect(int $code, bool $expected)
    {
        $code = HttpStatusCode::fromInt($code);
        $this->assertEquals($expected, $code->isRedirect());
    }

    public function provideRedirect()
    {
        yield [ '400', false];
        yield [ '301', true];
        yield [ '300', true ];
        yield [ '299', false ];
    }

    /**
     * @dataProvider provideError
     */
    public function testIsError(int $code, bool $expected)
    {
        $code = HttpStatusCode::fromInt($code);
        $this->assertEquals($expected, $code->isError());
    }

    public function provideError()
    {
        yield [ '501', true];
        yield [ '500', true];
        yield [ '400', true];
        yield [ '399', false];
        yield [ '301', false];
        yield [ '300', false];
        yield [ '299', false ];
    }
}
