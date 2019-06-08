# Changelog

## [0.9.0] 2019-06-08

- Record referrer title if client encounters error while requesting URL, fixes #88
- Verbose mode streams report to STDOUT with `--stdout` #83 #65

## [0.8.0] 2019-04-07

- Record the request timestamp #33
- Support setting the TLS security level (`--client-security-level`), fixes
  #52 (again)
- Sets the default TLS secutity level to 1 rather than 2.
- Set process title and adds version information to Phar #21
- `crawl` "command" renamed to `fink` #61

## [0.7.0] 2019-03-17

- Show referring link text and xpath #49.
- Allow display elements to be set, added or removed #54,
- Adds "uptime" display showing running time in hours, minutes and seconds.

## [0.6.0] 2019-03-14

- Support passing max header and body sizes to client #74
- Allow additional links to be included #60
- Resolve relative URLs to absolute URLs #64
- Show referrer title and Xpath #49

## [0.5.0] 2019-02-23

- Support for custom headers via. `--header=Foo:Bar` #46
- Support multiple base URLs #44
- Support setting the `--rate` (max number of requests per second) #51

## [0.4.1]

- SIGINT not defined

## [0.4.0] - 2019-02-16

- Do not include parent document query in child links #32
- Allow exclusion of URL patterns #30
- Handle SIGINT signal #39
- Introduced Dockerfile with lib event (much increase in throughput).
- Reads response body in chunks.

## [0.3.0] - 2019-02-02

- Unconditionally remove URL fragments.
- Do not inherit path from owner doucument's URL if path is missing..
- Show request rate.
- Allow specification of display buffer size (`--display-bufsize`).
- Support specification of client timeout #25.
- Support specification of client max redirects #26.
- Dispatch up to concurrency limit on each tick.

## [0.2.1] - 2019-01-26

- Use correct autoload path when included as a dependency

## [0.2.0] - 2019-01-26

- Support for loading cookie files h netscape cookie files.
- Support specifying the HTTP request interval with `--interval`.
- `--max-external-distance` (`-x`) option to limit "external" travel distance.
- Show rolling result when running.
- Output to CSV via, `--publisher=csv`.

## [0.1.0] - 2019-01-20

- Initial
