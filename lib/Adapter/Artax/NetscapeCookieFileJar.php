<?php

namespace DTL\Extension\Fink\Adapter\Artax;

use Amp\Artax\Cookie\ArrayCookieJar;
use Amp\Artax\Cookie\Cookie;
use DateTimeImmutable;
use RuntimeException;

class NetscapeCookieFileJar extends ArrayCookieJar
{
    public function __construct(string $filePath)
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException(sprintf(
                'Cookie file "%s" does not exist',
                $filePath
            ));
        }

        if (!$cookieFileHandle = fopen($filePath, 'r')) {
            throw new RuntimeException(sprintf(
                'Failed to open file "%s" for reading',
                $filePath
            ));
        }

        while (!feof($cookieFileHandle)) {
            if (!$line = fgets($cookieFileHandle)) {
                continue;
            }

            if (!$cookie = $this->parse($line)) {
                continue;
            }

            $this->store($cookie);
        }
    }

    private function parse(string $line): ?Cookie
    {
        $line = trim($line);

        if (empty($line)) {
            return null;
        }

        if (substr($line, 0, 1) === '#') {
            return null;
        }

        $parts = explode("\t", $line);

        // invalid cookie line, just ignore it
        if (count($parts) < 5) {
            return null;
        }

        $domain = $parts[0];
        $flag = $parts[1];
        $path = $parts[2];
        $secure = $parts[3];
        $expiration = $parts[4];
        $name = $parts[5];
        $value = @$parts[6];

        $expiration = DateTimeImmutable::createFromFormat('U', $expiration);

        // could not parse date
        if (false === $expiration) {
            return null;
        }

        $string = sprintf(
            '%s=%s; expires=%s; domain=%s; path=%s',
            $name,
            $value,
            $expiration->format('D, d M Y H:i:s T'),
            $domain,
            $path
        );

        if (strtolower($secure) === 'true') {
            $string .= '; secure';
        }

        return Cookie::fromString($string);
    }
}
