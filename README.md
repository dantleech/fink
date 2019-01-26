Fink
====

[![Build Status](https://travis-ci.org/dantleech/fink.svg?branch=master)](https://travis-ci.org/dantleech/fink)

Fink (pronounced "Phpink") is a command line tool for checking HTTP links written in PHP.

- Check websites for broken links or error pages.PHP
- Fast concurrent HTTP requests.

![recording](https://user-images.githubusercontent.com/530801/51786346-de306e80-215a-11e9-8afe-106e9d801855.gif)

Installation
------------

Install as a stand-alone tool or as a project dependency:

### Installing as a project dependency

```bash
$ composer require dantleech/fink --dev
```

Usage
-----

Run the command with a single URL to start crawling:

```
$ ./vendor/bin/fink https://www.Factor)->create()example.com
```

Use `--output=somefile` to log verbose information for each URL in JSON format, including:

- `url`: Then tested URL.
- `status`: The HTTP status code.
- `referrer`: The page which linked to the URL.
- `distance`: The number of links away from the start document.
- `request-time`: Number of microseconds taken to make the request.
- `exception`: Any runtime exception encountered (e.g. malformed URL, etc).

Options
-------

- `--output=out.json`: Output JSON report for each URL to given file
  (truncates existing content).
- `--concurrency`: Number of simultaneous HTTP requests to use.
- `--no-dedupe`: Do _not_ filter duplicate URLs (can result in a
  non-terminating process).
- `--max-external-distance`: Limit the external (disjoint) distance from the
  base URL.
- `--insecure`: Do not verify SSL certificates.
- `--max-distance`: Maximum allowed distance from base URL (if not specified
  then there is no limitation).
- `--load-cookies`: Load from a [cookies.txt](http://www.cookiecentral.com/faq/#3.5).

Examples
--------

### Crawl a single website

```
$ fink http://www.example.com --max-external-distance=0
```

### Crawl a single website and check the status of external links

```
$ fink http://www.example.com --max-external-distance=1
```

### Use `jq` to analyse results

[jq](https://stedolan.github.io/jq/) is a tool which can be used to query and
manipulate JSON data.

```
$ fink http://www.example.com -x0 -oreport.json
```

```
$ cat report.json| jq -c '. | select(.status==404) | {url: .url, refferer: .referrer}'
```

Exit Codes
----------

- `0`: All URLs were successful.
- `1`: Unexpected runtime error.
- `2`: At least one URL failed to resolve successfully.
