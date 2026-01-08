# Session

## Fixation-safe defaults

The session is configured with `use_only_cookies = 1` and `use_strict_mode = 1`
(both required to prevent session fixation) — these `SecurityOptions` are forced,
they **win over user-supplied options**. And `use_strict_mode` is backed at
runtime: on start, a fresh (empty) session whose id came from the client's cookie
is `regenerateId()`d to guarantee the id was actually created by the server, not
injected. `regenerateId()` is also the public hook to rotate the id after a
privilege change (e.g. login); it is idempotent per request.

## Smart auto-start is lazy on the write side

The three modes come from the DI extension (`session › autoStart:
smart|always|never`), but the *mechanism* lives in
`Session::autoStart(bool $forWrite)`:

- **`smart`** (default) calls `autoStart(false)` once at bootstrap: the session
  starts **only when the request carries a session cookie** (and
  `doStart(mustExists: true)` destroys it again when that cookie proves invalid —
  PHP regenerated the id). Without an existing session, reading accessors
  (`SessionSection::get()`, iteration, `getSectionNames()`) are no-ops; only a
  **write** (`set()`, `__set`, a non-null `setExpiration()`) calls
  `autoStart(true)` and creates the session. So an app that never writes never
  emits `Set-Cookie`. Note `getSection()` itself touches nothing — the lazy calls
  live in the `SessionSection` accessors.
- **`always`** starts immediately at bootstrap; **`never`** disables auto-start
  (an implicit write then warns) and requires a manual `start()`.

`autoStart` is a no-op once started, so it is safe to call from every section
accessor.

## Expiration lives in per-section metadata

Expiration is stored in the section's meta map, not in PHP's session lifetime:
`SessionSection::setExpiration($expire, $variables)` writes
`$meta[$variable]['T'] = <unix time>` — **per variable** when a name/array is
given, or **per section** under the `''` key otherwise. `set($name, $value,
$expire)` is sugar for a write plus `setExpiration`. Setting an expiration also
triggers `autoStart(true)` (it is a write; *clearing* one is not), and warns if
the requested lifetime exceeds `session.gc_maxlifetime` (which would let the GC
drop the data first).

## Write hook

`Session::clean()` fires `$onBeforeWrite` before the data is serialized to the
handler, giving a last chance to mutate section state; `$onStart` fires after the
session has started. `read_and_close` (single-request read) is honored by
`start()` and cannot be reconfigured once started.
