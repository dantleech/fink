<?php

namespace DTL\Extension\Fink\Tests\Unit\Adapter\Artax;

use Amp\Http\Client\Cookie\CookieJar;
use Amp\Http\Client\Request;
use Amp\Http\Cookie\ResponseCookie;
use Amp\Success;
use DTL\Extension\Fink\Adapter\Artax\ImmutableCookieJar;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

class ImmutableCookieJarTest extends TestCase
{
    public const EXAMPLE_DOMAIN = 'domain';
    public const EXAMPLE_PATH = self::EXAMPLE_VALUE;
    public const EXAMPLE_NAME = 'bar';
    public const EXAMPLE_VALUE = 'foo';


    /**
     * @var ObjectProphecy|CookieJar
     */
    private $innerJar;

    /**
     * @var ImmutableCookieJar
     */
    private $jar;

    public function setUp()
    {
        $this->innerJar = $this->prophesize(CookieJar::class);
        $this->jar = new ImmutableCookieJar($this->innerJar->reveal());
    }

    public function testDelegatesToInnerJarForGet()
    {
        $uri = (new Request('http://' . self::EXAMPLE_DOMAIN . self::EXAMPLE_PATH))->getUri();
        $returnValue = new Success(self::EXAMPLE_VALUE);

        $this->innerJar->get($uri)->willReturn($returnValue);

        $value = $this->jar->get($uri);

        $this->assertSame($returnValue, $value);
    }

    public function testNoOpsForMutableActions()
    {
        $this->innerJar->store(Argument::any())->shouldNotBeCalled();

        $this->jar->store(ResponseCookie::fromHeader('foo=bar'));
    }
}
