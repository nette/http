# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Nette HTTP Component** - A standalone PHP library providing HTTP abstraction for request/response handling, URL manipulation, session management, and file uploads. Part of the Nette Framework ecosystem but usable independently.

- **PHP Version**: 8.1 - 8.5
- **Package**: `nette/http`

## Essential Commands

### Running Tests

```bash
# Run all tests
vendor/bin/tester tests -s -C

# Run specific test file
php tests/Http/Request.files.phpt

# Run tests in specific directory
vendor/bin/tester tests/Http -s -C
```

### Static Analysis

```bash
# Run PHPStan
composer phpstan

# Or directly
vendor/bin/phpstan analyse
```

## Core Architecture

### Immutability Pattern

The codebase uses a sophisticated immutability strategy:

- **`Request`** - Immutable HTTP request with single wither method `withUrl()`
- **`Response`** - Mutable for managing response state (headers, cookies, status)
- **`UrlImmutable`** - Immutable URL with wither methods (`withHost()`, `withPath()`, etc.)
- **`Url`** - Mutable URL with setters for flexible building
- **`UrlScript`** - Extends UrlImmutable with script path information

**Design principle**: Data objects (Request) are immutable for integrity; state managers (Response, Session) are mutable for practical management.

### Security-First Design

Input sanitization is mandatory, not optional:

1. **RequestFactory** sanitizes ALL input:
   - Removes invalid UTF-8 sequences
   - Strips control characters (except tab, newline, carriage return)
   - Validates with regex: `[\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]`

2. **Secure defaults everywhere**:
   - Cookies are `httpOnly` by default
   - `SameSite=Lax` by default
   - Session uses strict mode and one-time cookies only
   - HTTPS auto-detection via proxy configuration

3. **FileUpload** security:
   - Content-based MIME detection (not client-provided)
   - `getSanitizedName()` removes dangerous characters
   - Documentation warns against trusting `getUntrustedName()`

### Key Components

#### Request (`src/Http/Request.php`)
- Immutable HTTP request representation
- Type-safe access to GET/POST/COOKIE/FILES/headers
- AJAX detection, same-site checking (`_nss` cookie, formerly `nette-samesite`), language detection
- Sanitized by RequestFactory before construction
- **Origin detection**: `getOrigin()` returns scheme + host + port for CORS validation
- **Basic Auth**: `getBasicCredentials()` returns `[user, password]` array
- **File access**: `getFile(['my-form', 'details', 'avatar'])` accepts array of keys for nested structures
- **Warning**: Browsers don't send URL fragments to the server (`$url->getFragment()` returns empty string)

#### Response (`src/Http/Response.php`)
- Mutable HTTP response management
- Header manipulation (set/add/delete)
- Cookie handling with security defaults (use `Response::SameSiteLax`, `SameSiteStrict`, `SameSiteNone` constants)
- Redirect, cache control, content-type helpers
- **Download support**: `sendAsFile('invoice.pdf')` triggers browser download dialog
- Checks `isSent()` to prevent modification after output starts
- **Cookie domain**: If specified, includes subdomains; if omitted, excludes subdomains

#### RequestFactory (`src/Http/RequestFactory.php`)
- Creates Request from PHP superglobals (`$_GET`, `$_POST`, etc.)
- Configurable proxy support (RFC 7239 Forwarded header + X-Forwarded-*)
- URL filters for cleaning malformed URLs
- File upload normalization into FileUpload objects

#### URL Classes
- **`Url`** - Mutable URL builder with setters, supports `appendQuery()` to add parameters
- **`UrlImmutable`** - Immutable URL with wither methods
  - `resolve($reference)` - Resolves relative URLs like a browser (v3.3.2+)
  - `withoutUserInfo()` - Removes user and password
- **`UrlScript`** - Request URL with virtual components:
  - `baseUrl` - Base URL including domain and path to app root
  - `basePath` - Path to application root directory
  - `scriptPath` - Path to current script
  - `relativePath` - Script name relative to basePath
  - `relativeUrl` - Everything after baseUrl (query + fragment)
  - `pathInfo` - Rarely used part after script name
- **Static helpers**:
  - `Url::isAbsolute($url)` - Checks if URL has scheme (v3.3.2+)
  - `Url::removeDotSegments($path)` - Normalizes path by removing `.` and `..` (v3.3.2+)
- All support IDN (via `ext-intl`), canonicalization, query manipulation

#### Session (`src/Http/Session.php` + `SessionSection.php`)
- **Auto-start modes**:
  - `smart` - Start only when session data is accessed (default)
  - `always` - Start immediately with application
  - `never` - Manual start required
- Namespaced sections to prevent naming conflicts
- **SessionSection API**: Use explicit methods instead of magic properties:
  - `$section->set('userName', 'john')` - Write variable
  - `$section->get('userName')` - Read variable (returns null if missing)
  - `$section->remove('userName')` - Delete variable
  - `$section->set('flash', $message, '30 seconds')` - Third parameter sets expiration
- Per-section or per-variable expiration
- Custom session handler support
- **Events**: `$onStart`, `$onBeforeWrite` - Callbacks invoked after session starts or before write to disk
- **Session ID management**: `regenerateId()` generates new ID (e.g., after login for security)

#### FileUpload (`src/Http/FileUpload.php`)
- Safe file upload handling
- Content-based MIME detection (requires `ext-fileinfo`)
- Image validation and conversion (supports JPEG, PNG, GIF, WebP, AVIF)
- Sanitized filename generation
- **Filename methods**:
  - `getUntrustedName()` - Original browser-provided name (⚠️ never trust!)
  - `getSanitizedName()` - Safe ASCII-only name `[a-zA-Z0-9.-]` with correct extension
  - `getSuggestedExtension()` - Extension based on MIME type (v3.2.4+)
  - `getUntrustedFullPath()` - Full path for directory uploads (PHP 8.1+, ⚠️ never trust!)

### Nette DI Integration

Two extensions provide auto-wiring:

1. **HttpExtension** (`src/Bridges/HttpDI/HttpExtension.php`)
   - **Registers**: `http.requestFactory`, `http.request`, `http.response`
   - **Configuration**: proxy IPs, headers, CSP, X-Frame-Options, cookie defaults
   - **CSP with nonce**: Automatically generates nonce for inline scripts
     ```neon
     http:
         csp:
             script-src: [nonce, strict-dynamic, self]
     ```
     Use in templates: `<script n:nonce>...</script>` - nonce filled automatically
   - **Cookie defaults**: `cookiePath`, `cookieDomain: domain` (includes subdomains), `cookieSecure: auto`
   - **X-Frame-Options**: `frames: SAMEORIGIN` (default) or `frames: true` to allow all

2. **SessionExtension** (`src/Bridges/HttpDI/SessionExtension.php`)
   - **Registers**: `session.session`
   - **Configuration**: `autoStart: smart|always|never`, expiration, handler, all PHP `session.*` directives in camelCase
   - **Tracy debugger panel**: Enable with `debugger: true` in config
   - **Session cookie**: Configure separately with `cookieDomain`, `cookieSamesite: Strict|Lax|None`

## Code Conventions

### Strict PHP Standards

Every file must start with:
```php
declare(strict_types=1);
```

### Modern PHP Features

Heavily uses PHP 8.1+ features:

```php
// Constructor property promotion with readonly
public function __construct(
    private readonly UrlScript $url,
    private readonly array $post = [],
    private readonly string $method = 'GET',
) {
}

// Named arguments
setcookie($name, $value, [
    'expires' => $expire ? (int) DateTime::from($expire)->format('U') : 0,
    'httponly' => $httpOnly ?? true,
    'samesite' => $sameSite ?? self::SameSiteLax,
]);

// First-class callables
Nette\Utils\Callback::invokeSafe(
    'session_start',
    [['read_and_close' => $this->readAndClose]],
    fn(string $message) => throw new Exception($message)
);
```

### Property Documentation with SmartObject

Uses `@property-read` magic properties with Nette's SmartObject trait:

```php
/**
 * @property-read UrlScript $url
 * @property-read array $query
 * @property-read string $method
 */
class Request implements IRequest
{
    use Nette\SmartObject;
}
```

### Testing with Nette Tester

Test files use `.phpt` extension and follow this pattern:

```php
<?php

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test('Url canonicalization removes duplicate slashes', function () {
    $url = new Url('http://example.com/path//to/../file.txt');
    $url->canonicalize();
    Assert::same('http://example.com/path/file.txt', (string) $url);
});

test('Url handles IDN domains', function () {
    $url = new Url('https://xn--tst-qla.de/');
    $url->canonicalize();
    Assert::same('https://täst.de/', (string) $url);
});
```

The `test()` helper is defined in `tests/bootstrap.php`.

## Development Guidelines

### When Adding Features

1. **Read existing code first** - Understand patterns before modifying
2. **Security first** - Consider injection, XSS, CSRF implications
3. **Maintain immutability contracts** - Don't add setters to immutable classes
4. **Test thoroughly** - Add `.phpt` test files in `tests/Http/`
5. **Check Windows compatibility** - Tests run on Windows in CI

### Common Patterns

**Proxy detection** - Use RequestFactory's `setProxy()` for trusted proxy IPs:
```php
$factory->setProxy(['127.0.0.1', '::1']);
```

**URL filtering** - Clean malformed URLs via urlFilters:
```php
// Remove spaces from path
$factory->urlFilters['path']['%20'] = '';

// Remove trailing punctuation
$factory->urlFilters['url']['[.,)]$'] = '';
```

**Cookie security** - Response uses secure defaults:
```php
// httpOnly=true, sameSite=Lax by default
$response->setCookie('name', 'value', '1 hour');
```

**Session sections** - Namespace session data with explicit methods:
```php
$section = $session->getSection('cart');
$section->set('items', []);
$section->setExpiration('20 minutes');

// Per-variable expiration
$section->set('flash', $message, '30 seconds');

// Events
$session->onBeforeWrite[] = function () use ($section) {
    $section->set('lastSaved', time());
};
```

## CI/CD Pipeline

GitHub Actions runs:

1. **Tests** (`.github/workflows/tests.yml`):
   - Matrix: Ubuntu/Windows/macOS × PHP 8.1-8.5 × php/php-cgi
   - Lowest dependencies test
   - Code coverage with Coveralls

2. **Static Analysis** (`.github/workflows/static-analysis.yml`):
   - PHPStan level 5 (informative only)

3. **Coding Style** (`.github/workflows/coding-style.yml`):
   - Nette Coding Standard (PSR-12 based)

## Architecture Principles

- **Single Responsibility** - Each class has one clear purpose
- **Dependency Injection** - Constructor injection, no service locators
- **Type Safety** - Everything typed (properties, parameters, returns)
- **Fail Fast** - Validation at boundaries, exceptions for errors
- **Framework Optional** - Works standalone or with Nette DI

## Important Notes

- **Browser behavior** - Browsers don't send URL fragments or Origin header for same-origin GET requests
- **Proxy configuration critical** - Required for correct IP detection and HTTPS detection
- **Session auto-start modes**:
  - `smart` - Starts only when `$section->get()`/`set()` is called (default)
  - `always` - Starts immediately on application bootstrap
  - `never` - Must call `$session->start()` manually
- **URL encoding nuances** - Respects PHP's `arg_separator.input` for query parsing
- **FileUpload validation** - Always check `hasFile()` and `isOk()` before processing
- **UrlScript virtual components** - Generated by RequestFactory, understand baseUrl vs basePath distinction
- **CSP nonce in templates** - Use `<script n:nonce>` for automatic nonce insertion with CSP headers
