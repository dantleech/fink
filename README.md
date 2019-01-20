Fink
====

[![Build Status](https://travis-ci.org/dantleech/fink.svg?branch=master)](https://travis-ci.org/dantleech/fink)

PHP Link Checker Command Line Tool

- Check websites for broken links or error pages.
- Fast concurrent HTTP requests.

Usage
-----

Install as a stand-alone tool or as a project dependency:

```bash
$ composer require dantleech/fink
$ ./vendor/bin/fink https://www.dantleech.com
Concurrency: 2, URL queue size: 10, Failures: 0/60 (0.00%)
https://www.dantleech.com/page/12
```
