<?php

namespace DTL\Extension\Fink\Tests\Unit\Adapter\Artax;

use Amp\Artax\Cookie\Cookie;
use Amp\Artax\Cookie\CookieJar;
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
        $this->innerJar->get(self::EXAMPLE_DOMAIN, self::EXAMPLE_PATH, self::EXAMPLE_NAME)->willReturn([self::EXAMPLE_VALUE]);

        $value = $this->jar->get(self::EXAMPLE_DOMAIN, self::EXAMPLE_PATH, self::EXAMPLE_NAME);

        $this->assertEquals([self::EXAMPLE_VALUE], $value);
    }

    public function testDelegatesToInnerJarForGetAll()
    {
        $this->innerJar->getAll()->willReturn(['foo' => self::EXAMPLE_VALUE]);

        $value = $this->jar->getAll();

        $this->assertEquals(['foo' => self::EXAMPLE_VALUE], $value);
    }

    public function testNoOpsForMutableActions()
    {
        $this->innerJar->remove(Argument::any())->shouldNotBeCalled();
        $this->innerJar->removeAll()->shouldNotBeCalled();
        $this->innerJar->store(Argument::any())->shouldNotBeCalled();

        $this->jar->remove(Cookie::fromString('foo=bar'));
        $this->jar->removeAll();
        $this->jar->store(Cookie::fromString('foo=bar'));
    }
}
