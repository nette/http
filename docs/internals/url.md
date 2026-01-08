# URL classes

Three classes, one parser, and a set of virtual components that are the actual
source of confusion.

## Only `Url` parses; the others delegate

- **`Url`** is mutable (setters return `$this`) and is the **only** class that
  parses. Its constructor runs `@parse_url()` and **throws
  `InvalidArgumentException` only when `parse_url` returns `false`**; missing
  components just default. `host`/`user`/`password`/`fragment` are `rawurldecode`d,
  `path`/`query` go through their setters, `scheme`/`port` are taken raw.
- **`UrlImmutable`** has withers (the authority-affecting ones reset the cached
  authority) and **does not parse** — its constructor takes a string only to build
  a temporary `Url` and copy its components via `export()`.
- **`UrlScript extends UrlImmutable`** and adds the script-path components below.

So the split of behavior matters: `resolve()` lives on `UrlImmutable`;
`appendQuery()`, `canonicalize()`, and the setters live on the mutable `Url`.
`isEqual()` exists on both, but the implementation is `Url`'s —
`UrlImmutable::isEqual()` delegates via a temporary `Url`.

## `canonicalize()` does less than its name suggests

`canonicalize()` (on `Url` only) re-encodes the path to a normal percent-encoding
and lowercases + IDN-decodes the host (trimming a trailing dot). It does **not**
remove `.`/`..` segments and does **not** collapse `//` — those are done elsewhere
(`RequestFactory::$urlFilters['path']` collapses `//`). A test that shows
`path//to/../file.txt → path/file.txt` is misleading about which layer does what.

## IDN is one-directional

The only IDN operation in the package is **ASCII→Unicode** (`idn_to_utf8`, guarded
by a `str_contains($host, '--')` heuristic and `INTL_IDNA_VARIANT_UTS46`), used by
`canonicalize()` and `isEqual()`. **Nothing ever encodes a Unicode host to
punycode** (`idn_to_ascii` is never called). Without `ext-intl` the host is
returned unchanged with an `E_USER_WARNING`.

## `resolve()` and `mergePath`

`UrlImmutable::resolve($reference)` is RFC 3986 relative resolution (inherit
scheme/host as the reference lacks them, `removeDotSegments` the result).
`Url::removeDotSegments()` is the static algorithm (preserves a leading `/` and a
trailing slash via an empty final segment). The subtle bit: **`UrlScript` overrides
`mergePath`** to merge against `basePath` rather than the script file, so relative
resolution against a request URL anchors at the application root.

## `UrlScript` virtual components are all derived from two fields

`UrlScript` stores only **`scriptPath` and `basePath`**; everything else is
computed:

- `basePath` = `scriptPath` truncated at its last `/` (so `basePath ⊆ scriptPath`).
- `baseUrl` = `hostUrl . basePath` (scheme + authority + basePath).
- `relativePath` = the path after `basePath`; `relativeUrl` = everything after
  `baseUrl` (path remainder + query + fragment); `pathInfo` = the path after
  `scriptPath`.

`setScriptPath` defaults an empty `scriptPath` to the whole path and validates
only that the *directory part* of `scriptPath` (up to its last `/`) is a prefix of
the real path (else throws) — the script's filename need not appear in the path.
Beware: `withPath()` without an explicit second argument silently **resets
`scriptPath` to the new path**. These are populated by `RequestFactory` (see
request.md), which is where the confusing `baseUrl` vs `basePath` distinction
originates.

## Query is stored as an array

`Url` keeps `query` as an array. Parsing respects `arg_separator.input`, decodes
`%5B`→`[`, and `parse_str`s; `getQuery()` always re-emits with `&` and
`PHP_QUERY_RFC3986`. `appendQuery(array)` unions with existing keys (existing win);
`appendQuery(string)` reparses the whole concatenation.
