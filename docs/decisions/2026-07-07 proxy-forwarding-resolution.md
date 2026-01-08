# Resolving the real client behind a trusted proxy

## Status

Accepted. Implemented in `RequestFactory::getClient()`, `useForwardedProxy()`,
`useNonstandardProxy()` and the shared helper `findClientHop()`.

## Context

When an application runs behind a reverse proxy, `REMOTE_ADDR` is the proxy's
address, not the client's. The client's real address, scheme and host are carried
by the proxy in forwarding headers: the standard `Forwarded` (RFC 7239) or the
de-facto `X-Forwarded-For` / `-Proto` / `-Host` / `-Port`. `setProxy()` defines
the list of trusted proxies; forwarding headers are read only when `REMOTE_ADDR`
matches one of them.

The original implementation had several flaws:

1. **Broken parsing of `Forwarded`.** `preg_split('/[,;]/')` merged the hop
   separator (`,`) with the parameter separator (`;`) into one flat set. The
   element structure was lost and `host`/`proto` could not be tied to the right
   hop. That forced a `count(...) === 1` guard which dropped `host`/`proto`
   entirely for any legitimate multi-hop header.
2. **Leftmost `for` = most spoofable.** `$proxyParams['for'][0]` was taken. Per
   RFC 7239 a proxy element *appends*, so the leftmost element is the one the
   client can forge. Injection passed even with a correctly configured proxy.
3. **Inconsistency.** `X-Forwarded-For` stripped trusted proxies and took the
   rightmost untrusted hop; `Forwarded` did not. Two different, partly incorrect
   implementations of the same thing.
4. **The result could be a non-IP string.** An obfuscated identifier
   (`for=unknown`, legal per RFC) or garbage was returned from
   `getRemoteAddress()`.

## Decision

- `Forwarded` is parsed per hop per RFC 7239: split into elements by `,`, then
  into parameters by `;`. The result is an ordered list of hops (leftmost =
  closest to the client).
- The client address is determined by the shared helper `findClientHop()` for
  both branches: it removes trailing trusted proxies from the chain and returns
  the rightmost remaining hop; its value is **validated as an IP**, otherwise
  `null` is returned.
- In the `Forwarded` branch, `host` and `proto` are taken **from the same hop as
  the client address** - i.e. from the element written by the trusted proxy
  facing the client. The client therefore cannot forge them, and multi-hop works
  without a crude guard.
- **All or nothing** in the `Forwarded` branch: if the client hop is not
  identified (no valid `for`), nothing from the header is trusted - not even
  `host`/`proto`. `null` is returned.
- The `X-Forwarded-*` branch is **per-header trust**, because these headers have
  no element structure. `X-Forwarded-Host` parallels `X-Forwarded-For` (it is
  read at the client hop's index and skipped without a valid client hop), but
  `X-Forwarded-Proto`/`-Port` carry no hop information (real proxies overwrite
  them rather than append), so they are applied whenever the proxy is trusted,
  independently of `X-Forwarded-For` - the long-standing behavior since 2016,
  and the per-header model Symfony and Laravel use.

## Configuring header selection

Which forwarding header a trusted proxy is believed for is configurable - this
closes the residual risk (see below) for anyone who knows what their proxy sets.

- **PHP API:** `setProxy($proxy, bool $forwarded = true, bool $xForwarded =
  true)`. Two independent switches, not an enum: the internal rule remains that
  when both are `true` and both headers arrive, `Forwarded` wins.
- **NEON:** a single readable word `proxyHeaders: xForwarded|forwarded|both|none`,
  which `HttpExtension` translates into those two booleans. A scalar is clearer to
  a human in the config than nested booleans; nobody needs a combination beyond
  `both`.
- **Two different defaults on purpose:** the PHP `setProxy()` keeps `both` (BC for
  direct library calls); the DI/NEON default is `xForwarded` (safe out of the box
  for a framework, where almost nobody uses `Forwarded` anyway).

## Rejected alternatives

- **Keep the leftmost `for` and the `count===1` guard.** Fixes neither spoofing
  nor multi-hop; it only patches the symptom.
- **Return a non-IP value when the rightmost hop is not an IP.**
  `getRemoteAddress()` must return an IP or `null`; a non-IP string is an error
  every consumer would have to handle.
- **Apply `proto`/`host` of a `Forwarded` header independently of `for`.**
  Without an identified client hop there is no trustworthy element to read them
  from; taking them from an arbitrary (client-inserted) hop is spoofable. Better
  to trust nothing - see Consequences.
- **Gate `X-Forwarded-Proto`/`-Port` behind a valid `for` as well.** It would
  break legitimate TLS-terminator setups that send only `X-Forwarded-Proto`,
  while not closing the pass-through risk (a forged proto arriving next to a
  valid `for` would still be accepted). The dangerous header, `X-Forwarded-Host`,
  *is* gated; proto's blast radius (scheme, `isSecured()`, cookie security) does
  not justify the BC break.
- **An enum instead of two booleans for header selection.**
  `Both/Forwarded/XForwarded/None` encodes two independent bits as four exclusive
  values; it does not scale to a third source (would be 2³ cases). The enum
  survives only as a NEON word (a facade), not as a type in the PHP API.
- **Change the PHP `setProxy()` default to `xForwarded` too.** It would harden
  direct library calls as well, but break BC; hardening at the configuration
  level is enough.

## Residual risk

The trust model believes whichever forwarding header the request carries.
Deciding by presence (`Forwarded` takes precedence over `X-Forwarded-For`) cannot
tell a header set by the proxy from one forged by the client. If the proxy manages
only `X-Forwarded-For` and passes the client's `Forwarded` through unchanged, that
passed-through `Forwarded` is used and trusted.

This **cannot be solved by parsing** and applies equally to Symfony/Laravel. It is
addressed by the header-selection configuration (above): the DI default
`xForwarded` ignores a passed-through client `Forwarded`, so a common deployment
is safe without intervention. The remaining condition: **a trusted proxy should
strip foreign forwarding headers it does not use** - documented by a comment in
`getClient()`.

## Consequences (BC)

- `getRemoteAddress()` returns `null` (not a non-IP string) when the innermost
  forwarded value is not a valid IP. This affects both branches.
- A `Forwarded` header containing only `proto`/`host` without `for` no longer sets
  the scheme/host (it did before). A deliberate consequence of the "all or
  nothing" rule; real proxies always send `for`.
- The DI/NEON default `proxyHeaders` is `xForwarded` (previously `Forwarded` was
  trusted too). A deployment whose proxy uses `Forwarded` must set
  `proxyHeaders: forwarded` or `both`. The PHP `setProxy()` default stays `both`.
