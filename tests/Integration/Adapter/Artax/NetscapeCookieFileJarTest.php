<?php

namespace DTL\Extension\Fink\Tests\Integration\Adapter\Artax;

use Amp\Http\Client\Request;
use Amp\Http\Cookie\ResponseCookie;
use DTL\Extension\Fink\Adapter\Artax\NetscapeCookieFileJar;
use DTL\Extension\Fink\Tests\IntegrationTestCase;
use DateTimeImmutable;
use RuntimeException;

class NetscapeCookieFileJarTest extends IntegrationTestCase
{
    public function testThrowsExceptionIfFileDoesNotExist()
    {
        $this->expectException(RuntimeException::class);
        new NetscapeCookieFileJar('asd.com');
    }

    public function testLoadCookies()
    {
        $expire = (new DateTimeImmutable())->modify('+1 day')->format('U');

        $cookies = <<<EOT
# Netscape HTTP Cookie File

.google.com	TRUE	/	FALSE	$expire	OGP	-5061451:
.google.com	TRUE	/	FALSE	$expire	OGPC	19010135-2:
.google.com	TRUE	/complete/search	FALSE	$expire	CGIC	hello
.php-fig.org	TRUE	/	FALSE	$expire	__utma	178696677.776937609.1544628769.1544628769.1544628769.1
.php-fig.org	TRUE	/	FALSE	$expire	__utmz	178696677.1544628769.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none)
.php-fig.org	TRUE	/	FALSE	$expire	__utmt	1
.php-fig.org	TRUE	/	FALSE	$expire	__utmb	178696677.1.10.1544628769
127.0.0.1	TRUE	/	FALSE	$expire	hello	world
this isn't valid
we ignore it
EOT
        ;

        $path = $this->workspace()->path('cookies.txt');
        file_put_contents($path, $cookies);

        $jar = new NetscapeCookieFileJar($path);

        $this->assertCount(4, \array_filter($jar->getAll(), static function (ResponseCookie $cookie) {
            return $cookie->getDomain() !== '.php-fig.org' || $cookie->getPath() !== '/';
        }));

        $this->assertEquals(
            '-5061451:',
            (yield $jar->get((new Request('https://google.com/'))->getUri()))[0]->getValue()
        );

        $this->assertEquals(
            '19010135-2:',
            (yield $jar->get((new Request('https://google.com/'))->getUri()))[1]->getValue()
        );

        $this->assertEquals(
            'hello',
            (yield $jar->get((new Request('https://google.com/complete/search'))->getUri()))[2]->getValue()
        );

        $this->assertEquals(
            '19010135-2:',
            (yield $jar->get((new Request('https://google.com/'))->getUri()))[1]->getValue()
        );

        $this->assertEquals(
            'world',
            (yield $jar->get((new Request('http://127.0.0.1/'))->getUri()))[0]->getValue()
        );
    }

    public function testLoadHttpOnlyCookies()
    {
        $expire = (new DateTimeImmutable())->modify('+1 day')->format('U');

        $cookies = <<<EOT
# Netscape HTTP Cookie File
# This is a comment and should be ignored

#HttpOnly_.example.com	TRUE	/	FALSE	$expire	session_id	abc123
#HttpOnly_example.com	FALSE	/admin	TRUE	$expire	admin_token	xyz789
.example.com	TRUE	/	FALSE	$expire	regular_cookie	normal_value
EOT
        ;

        $this->workspace()->put('httponly_cookies.txt', $cookies);
        $path = $this->workspace()->path('httponly_cookies.txt');

        $jar = new NetscapeCookieFileJar($path);

        $allCookies = $jar->getAll();

        // Should have 3 cookies total (2 httponly + 1 regular)
        $this->assertCount(3, $allCookies);

        // Get cookies for example.com
        $exampleCookies = yield $jar->get((new Request('https://example.com/'))->getUri());

        // Should include the httponly cookie
        $cookieNames = array_map(fn ($cookie) => $cookie->getName(), $exampleCookies);
        $this->assertContains('session_id', $cookieNames);
        $this->assertContains('regular_cookie', $cookieNames);

        // Verify httponly cookie value
        $sessionCookie = array_filter($exampleCookies, fn ($cookie) => $cookie->getName() === 'session_id');
        $this->assertEquals('abc123', reset($sessionCookie)->getValue());
    }
}
