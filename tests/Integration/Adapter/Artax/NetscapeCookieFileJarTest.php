<?php

namespace DTL\Extension\Fink\Tests\Integration\Adapter\Artax;

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

        $this->assertCount(4, $jar->getAll()['.php-fig.org']['/']);

        $this->assertEquals(
            '-5061451:',
            $jar->get('.google.com', '', 'OGP')[0]->getValue()
        );
        $this->assertEquals(
            '19010135-2:',
            $jar->get('.google.com', '/', 'OGPC')[0]->getValue()
        );
        $this->assertEquals(
            'hello',
            $jar->get('.google.com', '/complete/search', 'CGIC')[0]->getValue()
        );
        $this->assertEquals(
            '19010135-2:',
            $jar->get('.google.com', '/', 'OGPC')[0]->getValue()
        );
        $this->assertEquals(
            'world',
            $jar->get('127.0.0.1', '/', 'hello')[0]->getValue()
        );
    }
}
