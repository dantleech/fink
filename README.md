Fink
====

[![Build Status](https://travis-ci.org/dantleech/fink.svg?branch=master)](https://travis-ci.org/dantleech/fink)

Fink (pronounced "Phpink") is a command line tool for checking HTTP links written in PHP.

- Check websites for broken links or error pages.
- Asynchronous HTTP requests.

![recording](https://user-images.githubusercontent.com/530801/55685040-e4f11400-5949-11e9-9f79-51c5c23a40c0.gif)

Installation
------------

Install as a stand-alone tool or as a project dependency:

### Installing as a project dependency

```bash
$ composer require dantleech/fink --dev
```

### Installing from a PHAR

Download the PHAR from the
[Releases](https://github.com/dantleech/fink/releases) page.

### Building your own PHAR with Box

You can build your own PHAR by cloning this repository and running:

```bash
$ ./vendor/bin/box compile
```

Usage
-----

Run the command with a single URL to start crawling:

```
$ ./vendor/bin/fink https://www.example.com
```

Use `--output=somefile` to log verbose information for each URL in JSON format, including:

- `url`: The tested URL.
- `status`: The HTTP status code.
- `referrer`: The page which linked to the URL.
- `referrer_title`: The value (e.g. link title) of the referring element.
- `referrer_xpath`: The path to the node in the referring document.
- `distance`: The number of links away from the start document.
- `request_time`: Number of microseconds taken to make the request.
- `timestamp`: The time that the request was made.
- `exception`: Any runtime exception encountered (e.g. malformed URL, etc).

Arguments
---------

- `url` (multiple) Specify one or more base URLs to crawl (mandatory).

Options
-------

- `--client-max-body-size` 'Max body size for HTTP client (in bytes).
- `--client-max-header-size` 'Max header size for HTTP client (in bytes).
- `--client-redirects=5` Set the maximum number of times the client should redirect (`0` to never redirect).
- `--client-security-level=1` Set the default SSL [secutity
  level](https://www.openssl.org/docs/manmaster/man3/SSL_CTX_set_security_level.html)
- `--client-timeout=15000` Set the maximum amount of time (in milliseconds)
  the client should wait for a response, defaults to 15,000 (15 seconds).
- `--concurrency`: Number of simultaneous HTTP requests to use.
- `--display-bufsize=10` Set the number of URLs to consider when showing the
  display.
- `--display=+memory` Set, add or remove elements of the runtime display
  (prefix with `-` or `+` to modify the default set).
- `--exclude-url=logout` (multiple) Exclude URLs matching the given PCRE pattern.
- `--header="Foo: Bar"` (multiple) Specify custom header(s).
- `--include-link=foobar.html` Include given link as if it were linked from the
  base URL.
- `--insecure`: Do not verify SSL certificates.
- `--load-cookies`: Load from a [cookies.txt](http://www.cookiecentral.com/faq/#3.5).
- `--max-distance`: Maximum allowed distance from base URL (if not specified
  then there is no limitation).
- `--max-external-distance`: Limit the external (disjoint) distance from the
  base URL.
- `--no-dedupe`: Do _not_ filter duplicate URLs (can result in a
  non-terminating process).
- `--output=out.json`: Output JSON report for each URL to given file
  (truncates existing content).
- `--publisher=csv` Set the publisher (defaults to `json`) can be either
  `json` or `csv`.
- `--rate` Set a maximum number of requests to make in a second.
- `--stdout` Stream to STDOUT directly, disables display and any specified outfile.

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
$ cat report.json| jq -c '. | select(.status==404) | {url: .url, referrer: .referrer}' | jq
```

Exit Codes
----------

- `0`: All URLs were successful.
- `1`: Unexpected runtime error.
- `2`: At least one URL failed to resolve successfully.
