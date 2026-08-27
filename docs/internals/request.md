# Request construction, sanitization & input

`RequestFactory` turns PHP superglobals into an immutable `Request`. The security
posture is set here, before `Request` ever exists.

## Sanitization is mandatory — but not universal

`RequestFactory` filters GET, POST, COOKIE, and file **names** against a
whitelist `ValidChars = '\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}'` (tab/LF/CR,
printable ASCII, and `\xA0`+): a **key** that fails is `unset` entirely, a string
**value** has non-matching bytes stripped (the `u` modifier also drops invalid
UTF-8), and a non-string/array value throws. This runs recursively into nested
arrays.

Two non-obvious boundaries:

- **HTTP headers are NOT sanitized.** `getHeaders()` applies no `ValidChars`
  filter; headers reach `Request` raw (only lowercased keys). Anything reading a
  header must treat it as untrusted.
- **`setBinary()` disables only this input filtering**, not the URL path handling
  (`urlFilters` and the `Strings::fixEncoding` on the path always run).

So the invariant "nothing in `Request` is raw" holds for query/post/cookie/file
names, with headers as the deliberate exception.

## Building the `UrlScript`

`fromGlobals` assembles the URL (scheme from `HTTPS`/proxy, host/port from
`HTTP_HOST` with `SERVER_NAME`+`SERVER_PORT` as fallback), applies `urlFilters`
(default `//`→`/` on the path), sanitizes, then
resolves the client via the proxy chain (see proxy.md). When no host came out of
any of that (CLI), the URL configured via `setBaseUrl()` (`http: baseUrl`) supplies
scheme, host, port and path instead; the environment always wins when it has a
host. `setForceHttps()` overrides the scheme to `https` after that.

The `scriptPath` is derived by comparing the request path against
`$_SERVER['SCRIPT_NAME']` (case-insensitively): it finds their common prefix and
truncates to the last `/` within the match (`/` under `cli-server` or on no
match). With the base-URL fallback the comparison is skipped and `scriptPath` is
the base path itself, because `SCRIPT_NAME` names the CLI script. That
`scriptPath` seeds `UrlScript`, from which `basePath` and the other
virtual components follow (see url.md).

## File-upload normalization

`getFiles()` walks `$_FILES` breadth-first (not recursively), turning PHP's
parallel `name`/`type`/`size`/`tmp_name`/`error` arrays for a nested field like
`my-form[details][avatar]` into a **tree of `FileUpload` objects**, wired back into
the result by reference. A `UPLOAD_ERR_NO_FILE` slot produces no object. Names are
sanitized unless in binary mode.

`FileUpload` itself never trusts the client: `getContentType()` uses
`finfo` on the temp file (the client's `type` is not even stored),
`getSanitizedName()` produces an ASCII `[a-zA-Z0-9.-]` name (and only fixes the
extension for images, via `getSuggestedExtension`), while `getUntrustedName()` /
`getUntrustedFullPath()` are explicitly "do not trust". Check `isOk()`
(`error === UPLOAD_ERR_OK`) / `hasFile()` before use.

## Cookies and headers on the response

`Response` is mutable and guards every mutation with `headers_sent()`
(`isSent()`); `setCookie`/`setHeader`/`setCode` throw once output has started.
`setCookie()` builds the `Set-Cookie` header itself (not via PHP's `setcookie()`),
sending both `expires` and `Max-Age`, and supports the `Partitioned` (CHIPS)
attribute. Cookie **defaults are secure**: `httpOnly` true, `SameSite=Lax`,
`secure` from the factory default — and `Secure` is **forced** whenever
`SameSite=None` or `Partitioned` is used (browsers reject them without it).

Two presence-encoded gotchas: an **explicit `domain` emits a `Domain=` attribute**
(which the browser treats as including subdomains); an **omitted domain sends
none**, yielding a host-only cookie. And the defaults are cross-wired: passing an
explicit `domain` defaults the path to `/` (not `$cookiePath`), passing an
explicit `path` defaults the domain to `''` (not `$cookieDomain`).

## Same-site detection (current model)

`Request::isFrom($site, $dest, $user)` checks the `Sec-Fetch-Site` /
`Sec-Fetch-Dest` / `Sec-Fetch-User` headers against the `FetchSite` / `FetchDest`
enums. When `Sec-Fetch-Site` is absent (Safari < 16.4), it falls back to the
presence of the `SameSite=Strict` `_nss` cookie (`Helpers::StrictCookieName`) —
but the fallback can only confirm a non-cross-site request: it answers `true` only
when the cookie is present, the asked-for sites include something other than
`CrossSite`, and **no `$dest`/`$user` constraint was given** (those force `false`).
`Helpers::initCookie()` sends `_nss` only to browsers that did not send
`Sec-Fetch-Site`. `isSameSite()` is deprecated sugar for
`isFrom([FetchSite::SameSite, FetchSite::SameOrigin])`.

`getOrigin()` returns a `UrlImmutable` (scheme + host + port) from the `Origin`
header, or `null` when the header is absent or malformed (including the literal
`Origin: null`) — used for CORS checks, but note the browser omits `Origin` on
same-origin GET.
