<?php

namespace DTL\Extension\Fink\Adapter\Artax;

use Amp\Http\Client\Cookie\ArrayCookieJar;
use Amp\Http\Cookie\ResponseCookie;
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

    private function parse(string $line): ?ResponseCookie
    {
        $line = trim($line);

        if (empty($line)) {
            return null;
        }

        if ($line[0] === '#') {
            return null;
        }

        $parts = explode("\t", $line);

        // invalid cookie line, just ignore it
        if (count($parts) < 5) {
            return null;
        }

        [$domain, $flag, $path, $secure, $expiration, $name, $value] = $parts + [6 => null];

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

        return ResponseCookie::fromHeader($string);
    }
}
