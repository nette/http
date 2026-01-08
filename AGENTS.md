# To My Agents!

It is my fervent wish that this file guide every AI coding agent working with code in this repository.

## Documentation

Any distilled, agent-facing documentation for this package - how it works
internally and the rationale behind key design decisions - lives in `docs/`.
Consult it before non-trivial changes; it is the source of truth from which the
public manual is distilled.

This is a security-critical package: read the internals before touching URL
parsing, input sanitization, the reverse-proxy trust model, cookies, or sessions.
A subtle change there can open an injection, spoofing, or fixation hole.

## Project Overview

**Nette HTTP Component** - A standalone PHP library providing HTTP abstraction for
request/response handling, URL manipulation, session management, and file uploads.
Part of the Nette Framework ecosystem but usable independently.

- **PHP Version**: 8.3 - 8.5
- **Package**: `nette/http`

## Essential Commands

```bash
# Run all tests
vendor/bin/tester tests -s

# Run a single test file
php tests/Http/Request.files.phpt

# Static analysis (PHPStan level 8, mandatory - the build fails on any error)
composer phpstan
```

## Conventions

- Every file starts with `declare(strict_types=1);`.
- Tests are Nette Tester `.phpt` files under `tests/Http/`, using the `test()`
  helper from `tests/bootstrap.php`. CI runs the suite on Linux, Windows, and
  macOS - keep new tests Windows-safe.
- Coding style follows the Nette Coding Standard (PSR-12 based); PHPStan runs at
  level 8 and the build fails on any error.

## Working in this repo

- **Security first.** Input sanitization, cookie/session secure defaults, and the
  proxy trust model are load-bearing. Consider injection, XSS, CSRF, SSRF, and
  session-fixation implications before changing them, and check `docs/internals`
  for the exact invariants.
- **Respect immutability contracts.** `Request`, `UrlImmutable`, and `UrlScript`
  are immutable (withers only) - don't add setters. `Url`, `Response`, and
  `Session` are their mutable counterparts by design.
