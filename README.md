Fink
====

[![Build Status](https://travis-ci.org/dantleech/fink.svg?branch=master)](https://travis-ci.org/dantleech/fink)

Fink (pronounced "Phpink") is a command line tool for checking HTTP links written in PHP.

- Check websites for broken links or error pages.PHP
- Fast concurrent HTTP requests.

![recording](https://user-images.githubusercontent.com/530801/51439839-c28b1b00-1cb7-11e9-9538-cf7c7b8215b4.gif)

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
$ ./vendor/bin/fink crawl https://www.example.com
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
- `--descendants-only`: Only crawl direct descendnats of the given URL
- `--first-external-only`: Like descendants-only but check the statuscode of the first External URL
- `--insecure`: Do not verify SSL certificates.
- `--max-distance`: Maximum allowed distance from base URL (if not specified
  then there is no limitation).
- `--load-cookies`: Load from a [cookies.txt](http://www.cookiecentral.com/faq/#3.5).

Exit Codes
----------

- `0`: All URLs were successful.
- `1`: Unexpected runtime error.
- `2`: At least one URL failed to resolve successfully.
