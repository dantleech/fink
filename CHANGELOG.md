# Changelog

## Unreleased

- Allow additional links to be included #60
- Resolve relative URLs to absolute URLs #64

## [0.5.0]

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
