<?php

namespace DTL\Extension\Fink\Console;

use DTL\Extension\Fink\Console\Exception\CouldNotParseHeader;

final class HeaderParser
{
    public function parseHeaders(array $rawHeaders)
    {
        $headers = [];
        $invalids = [];

        foreach ($rawHeaders as $rawHeader) {
            $parts = array_map('trim', explode(':', $rawHeader));

            if (count($parts) !== 2) {
                $invalids[] = $rawHeader;
                continue;
            }
            $headers[$parts[0]] = $parts[1];
        }

        if (!empty($invalids)) {
            throw new CouldNotParseHeader(sprintf(
                'Could not parse given headers "%s"',
                implode('", "', $invalids)
            ));
        }

        return $headers;
    }
}
