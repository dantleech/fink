Fink
====

[![Build Status](https://travis-ci.org/dantleech/fink.svg?branch=master)](https://travis-ci.org/dantleech/fink)

PHP Link Checker Command Line Tool

- Check websites for broken links or error pages.
- Fast concurrent HTTP requests.

![recording](https://user-images.githubusercontent.com/530801/51439839-c28b1b00-1cb7-11e9-9538-cf7c7b8215b4.gif)

Usage
-----

Install as a stand-alone tool or as a project dependency:

```bash
$ composer require dantleech/fink
$ ./vendor/bin/fink https://www.dantleech.com
Concurrency: 2, URL queue size: 10, Failures: 0/60 (0.00%)
https://www.dantleech.com/page/12
```

Options
-------

- `--output=out.json`: Output JSON report for each URL to given file
  (truncates existing content).
- `--concurrency`: Number of simultaneous HTTP requests to use.
- `--no-dedupe`: Do _not_ filter duplicate URLs (can result in a
  non-terminating process).
- `--descendants-only`: Only crawl direct descendnats of the given URL
- `--insecure`: Do not verify SSL certificates.
- `--max-distance`: Maximum allowed distance from base URL (if not specified
  then there is no limitation).

Exit Codes
----------

- `0`: All URLs were successful.
- `2`: At least one URL failed to resolve successfully.
