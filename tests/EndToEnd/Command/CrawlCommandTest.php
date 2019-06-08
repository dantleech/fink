<?php

namespace DTL\Extension\Fink\Tests\EndToEnd\Command;

use DTL\Extension\Fink\Tests\EndToEnd\EndToEndTestCase;

class CrawlCommandTest extends EndToEndTestCase
{
    private const EXAMPLE_URL = 'http://127.0.0.1:8124';

    public function testCrawlsUrl()
    {
        $process = $this->execute([
            self::EXAMPLE_URL
        ]);
        $this->assertProcessSuccess($process);
    }

    public function testPublishesReport()
    {
        $process = $this->execute([
            '--output='.$this->workspace()->path('/out.json'),
            self::EXAMPLE_URL,
        ]);

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertCount(6, $rows);
        $this->assertStatus($rows, 200, 'blog.html');
        $this->assertStatus($rows, 200, 'about.html');
        $this->assertStatus($rows, 200, 'posts/post1.html');
        $this->assertStatus($rows, 200, 'posts/post2.html');
        $this->assertStatus($rows, 404, '404.html');
    }

    public function testLimitsExternalDistance()
    {
        $process = $this->execute([
            self::EXAMPLE_URL . '/posts/external',
            '--output='.$this->workspace()->path('/out.json'),
            '--max-external-distance=1'
        ]);

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertCount(2, $rows);
        $this->assertStatus($rows, 200, 'posts');
        $this->assertStatus($rows, 200, 'posts/external');
    }

    public function testAllowsUrlDuplication()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--output='.$this->workspace()->path('/out.json'),
            '--no-dedupe',
            '--max-distance=3'
        ]);

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertUrlCount($rows, 2, 'blog.html');
        $this->assertUrlCount($rows, 1, 'posts/post1.html');
    }

    public function testConcurrencyCanBeSet()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--output='.$this->workspace()->path('/out.json'),
            '--concurrency=20'
        ]);

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertUrlCount($rows, 1, 'blog.html');
        $this->assertUrlCount($rows, 1, 'about.html');
    }

    public function testDisableSslVerfication()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--insecure',
        ]);

        $this->assertProcessSuccess($process);
    }

    public function testSpecifyMaxDistanceFromTheBaseDocument()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--max-distance=1',
            '--output='.$this->workspace()->path('/out.json'),
        ], 'website');

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertStatus($rows, 200, 'blog.html');
        $this->assertStatus($rows, 200, 'about.html');
        $this->assertUrlCount($rows, 0, 'posts/post1.html');
        $this->assertUrlCount($rows, 0, 'posts/post2.html');
    }

    public function testCannotAccessCookieProtectedPageWithoutCookie()
    {
        $process = $this->execute([
            self::EXAMPLE_URL . '/cookie.php',
            '--output='.$this->workspace()->path('/out.json'),
        ], 'cookie-protected');

        $this->assertProcessSuccess($process);
        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertStatus($rows, 403, 'cookie.php');
    }

    public function testCanAccessProtectedPageWithCookieAppropriateNetscapeCookieFile()
    {
        $process = $this->execute([
            self::EXAMPLE_URL . '/cookie.php',
            '--load-cookies=' . __DIR__ . '/../../Example/cookie-protected/cookies.txt'  ,
            '--output='.$this->workspace()->path('/out.json'),
        ], 'cookie-protected');

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertStatus($rows, 200, 'cookie.php');
    }

    public function testSpecifyRequestPollInterval()
    {
        $process = $this->execute([
            self::EXAMPLE_URL . '/',
            '--interval=10',
            '--output='.$this->workspace()->path('/out.json'),
        ], 'website');

        $this->assertProcessSuccess($process);
    }

    public function testExitsWithErrorIfCookieFileNotFound()
    {
        $process = $this->execute([
            self::EXAMPLE_URL . '/cookie.php',
            '--load-cookies=' . __DIR__ . '/idontexist.txt'  ,
            '--output='.$this->workspace()->path('/out.json'),
        ], 'cookie-protected');

        $this->assertEquals(1, $process->getExitCode());
    }

    public function testExitsWith128PlusSigintOnSigint()
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('pcntl extension is not loaded');
        }
        $server = $this->serve('website');
        $process = $this->finkProcess([
            self::EXAMPLE_URL,
            '--concurrency=1',
            '--interval=10000',
        ]);

        $process->start();
        $process->waitUntil(function ($error, $data) {
            return (bool) $data;
        });
        $process->signal(SIGINT);
        $process->wait();

        $server->stop();

        $this->assertEquals(130, $process->getExitCode());
        $this->assertContains('SIGINT received', $process->getOutput());
    }


    public function testPublishToCsv()
    {
        $process = $this->execute([
            self::EXAMPLE_URL . '/cookie.php',
            '--publisher=csv',
            '--output='.$this->workspace()->path('/out.json'),
        ]);

        $this->assertProcessSuccess($process);
    }

    public function testSpecifyDisplayBufSize()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--display-bufsize=2',
        ]);

        $out = $process->getOutput();
        $this->assertEquals(2, substr_count($out, self::EXAMPLE_URL));
        $this->assertProcessSuccess($process);
    }

    public function testSpecifyMaxTimeout()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--client-timeout=1000',
        ]);

        $this->assertProcessSuccess($process);
    }

    public function testMaxRedirects()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--client-redirects=0',
        ]);

        $this->assertProcessSuccess($process);
    }

    public function testClientMaxHeaderSize()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--client-max-header-size=8192',
        ]);

        $this->assertProcessSuccess($process);
    }

    public function testClientSslSecurityLevel()
    {
        if (\OPENSSL_VERSION_NUMBER < 0x10100000) {
            $this->markTestSkipped('OpenSSL version does not support setting secutity level');
            return;
        }

        $process = $this->execute([
            self::EXAMPLE_URL,
            '--client-security-level=4',
        ]);

        $this->assertProcessSuccess($process);
    }

    public function testClientMaxBodySize()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--client-max-body-size=8192',
        ]);

        $this->assertProcessSuccess($process);
    }

    public function testCustomHeaders()
    {
        $process = $this->execute([
            self::EXAMPLE_URL . '/teapot.php',
            '--header=X-One: Teapot',
            '--header=X-Two: Pottea',
            '--output='.$this->workspace()->path('/out.json'),
        ], 'custom-headers');

        $this->assertProcessSuccess($process);
        $rows = $this->parseResults($this->workspace()->path('/out.json'));
        $this->assertStatus($rows, 418, 'teapot.php');
    }

    public function testExcludesUrlPatterns()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--output='.$this->workspace()->path('/out.json'),
            '--exclude-url=post1.html',
            '--exclude-url=post2.html'
        ], 'website');

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertUrlCount($rows, 0, 'posts/post1.html');
        $this->assertUrlCount($rows, 1, 'blog.html');
        $this->assertUrlCount($rows, 0, 'posts/post2.html');
    }

    public function testCrawlsMultipleUrls()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            self::EXAMPLE_URL . '/hidden.html',
            '--output='.$this->workspace()->path('/out.json'),
        ], 'website');

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertUrlCount($rows, 1, 'blog.html');
        $this->assertUrlCount($rows, 1, 'about.html');
        $this->assertUrlCount($rows, 1, 'hidden.html');
        $this->assertUrlCount($rows, 1, 'hidden/secret.html');
    }

    public function testCrawlsGivenHiddenUrls()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            self::EXAMPLE_URL . '/hidden.html',
            '--include-link=/hidden.html',
            '--output='.$this->workspace()->path('/out.json'),
        ], 'website');

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertUrlCount($rows, 1, 'blog.html');
        $this->assertUrlCount($rows, 1, 'about.html');
        $this->assertUrlCount($rows, 1, 'hidden.html');
        $this->assertUrlCount($rows, 1, 'hidden/secret.html');
        $this->assertUrlCount($rows, 1, 'hidden/secret1.html');
        $this->assertUrlCount($rows, 1, 'hidden/secret2.html');
    }

    public function testRateLimiting()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--rate=2',
            '--output='.$this->workspace()->path('/out.json'),
        ], 'website');

        $this->assertProcessSuccess($process);
    }

    public function testShowsTheReferringLinkText()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--output='.$this->workspace()->path('/out.json'),
        ]);
        $this->assertProcessSuccess($process);
        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertUrlCount($rows, 1, 'posts/post1.html');
        $url = $this->findUrl($rows, 'posts/post1.html');
        $this->assertNotNull($url);
        $this->assertEquals('Post 1', $url['referrer_title']);
        $this->assertEquals('/html/body/ul/li[1]/a', $url['referrer_xpath']);
    }

    public function testShowsReferrerIfExceptionEncountered()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--output='.$this->workspace()->path('/out.json'),
        ], 'malformed-host');
        $this->assertProcessSuccess($process);
        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertCount(2, $rows);
        $row = $rows[1];
        $this->assertNotNull($row['exception']);
        $this->assertEquals('This is a link', $row['referrer_title']);
    }

    public function testAllowsDisplayCustomization()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--display=status'
        ]);
        $this->assertProcessSuccess($process);
    }

    public function testStreamsToStdout()
    {
        $process = $this->execute([
            self::EXAMPLE_URL,
            '--stdout',
        ]);
        $this->assertProcessSuccess($process);
        $this->assertStringContainsString('blog.html', $process->getOutput());
    }

    private function assertStatus(array $results, int $code, string $target): void
    {
        $target = self::EXAMPLE_URL . '/'. $target;
        foreach ($results as $result) {
            if ($result['url'] === $target) {
                $this->assertEquals($code, $result['status'], $target);
                return;
            }
        }

        $this->fail(sprintf('URL "%s" not found in results', $target));
    }

    private function assertUrlCount(array $rows, int $count, string $target)
    {
        $target = self::EXAMPLE_URL . '/'. $target;
        $this->assertCount($count, array_filter($rows, function (array $row) use ($target) {
            return $row['url'] === $target;
        }), $target);
    }

    private function findUrl(array $rows, string $target): ?array
    {
        foreach ($rows as $row) {
            if (preg_match('{' . $target . '$}', $row['url'])) {
                return $row;
            }
        }

        return null;
    }
}
