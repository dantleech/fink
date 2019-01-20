<?php

namespace DTL\Extension\Fink\Tests\EndToEnd\Command;

use DTL\Extension\Fink\Tests\EndToEnd\EndToEndTestCase;

class CrawlCommandTest extends EndToEndTestCase
{
    const EXAMPLE_URL = 'http://127.0.0.1:8124';

    public function testCrawlsUrl()
    {
        $process = $this->execute(['crawl', self::EXAMPLE_URL]);
        $this->assertProcessSuccess($process);
    }

    public function testCrawlsUrlPublishesReport()
    {
        $process = $this->execute([
            'crawl',
            '--output='.$this->workspace()->path('/out.json'),
            self::EXAMPLE_URL,
        ]);

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertStatus($rows, 200, 'blog.html');
        $this->assertStatus($rows, 200, 'about.html');
        $this->assertStatus($rows, 200, 'posts/post1.html');
        $this->assertStatus($rows, 200, 'posts/post2.html');
        $this->assertStatus($rows, 404, '404.html');
    }

    public function testCrawlsDescendantsOnly()
    {
        $process = $this->execute([
            'crawl',
            self::EXAMPLE_URL . '/posts',
            '--output='.$this->workspace()->path('/out.json'),
            '--descendants-only'
        ]);

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertUrlCount($rows, 0, 'blog.html');
        $this->assertUrlCount($rows, 0, 'about.html');
        $this->assertStatus($rows, 200, 'posts/post1.html');
        $this->assertStatus($rows, 200, 'posts/post2.html');
    }

    public function testAllowsUrlDuplication()
    {
        $process = $this->execute([
            'crawl',
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

    public function testAllowsTheConcurrencyToBeSet()
    {
        $process = $this->execute([
            'crawl',
            self::EXAMPLE_URL,
            '--output='.$this->workspace()->path('/out.json'),
            '--concurrency=20'
        ]);

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertUrlCount($rows, 1, 'blog.html');
        $this->assertUrlCount($rows, 1, 'about.html');
    }

    public function testInsecure()
    {
        $process = $this->execute([
            'crawl',
            self::EXAMPLE_URL,
            '--insecure',
        ]);

        $this->assertProcessSuccess($process);
    }

    public function testMaxDisatance()
    {
        $process = $this->execute([
            'crawl',
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

    public function testUsesCookieFile()
    {
        $process = $this->execute([
            'crawl',
            self::EXAMPLE_URL . '/cookie.php',
            '--load-cookies=' . __DIR__ . '/../../Example/cookie-protected/cookies.txt'  ,
            '--output='.$this->workspace()->path('/out.json'),
        ], 'cookie-protected');

        $this->assertProcessSuccess($process);

        $rows = $this->parseResults($this->workspace()->path('/out.json'));

        $this->assertStatus($rows, 200, 'cookie.php');
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
        }));
    }
}
