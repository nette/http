# Proxy trust model (client resolution)

Behind a reverse proxy, `REMOTE_ADDR` is the proxy, not the client. Resolving the
real client — and doing it without letting the client forge it — is the
security-critical logic in `RequestFactory::getClient`, and it has a precise
contract (see `docs/decisions/2026-07-07 proxy-forwarding-resolution.md` for the
full rationale).

## Trust gate

Forwarding headers are read **only when `REMOTE_ADDR` matches a trusted proxy**
from `setProxy()`. `getClient()` then chooses a branch: `Forwarded` (RFC 7239) via
`useForwardedProxy` when trusted and present, else `X-Forwarded-*` via
`useNonstandardProxy`. When both are trusted and both arrive, **`Forwarded` wins**.

## The four invariants

1. **`Forwarded` is parsed per hop:** split into elements by `,`, then into
   parameters by `;`, yielding an ordered hop list (leftmost = closest to the
   client). The earlier flat `preg_split('/[,;]/')` merged the separators and lost
   hop structure.
2. **The client is the rightmost *untrusted* hop, not the leftmost.**
   `findClientHop()` strips trailing trusted proxies and returns the innermost
   remaining hop. The leftmost `for` is the one a client can *append* and forge, so
   taking it was the spoofing bug.
3. **`host` and `proto` are taken from the same hop as the client `for`** — the
   element written by the trusted proxy facing the client — so they cannot be
   forged and multi-hop needs no special-case. *(`Forwarded` branch only; see
   the next section for `X-Forwarded-*`.)*
4. **All or nothing** *(`Forwarded` branch only)*: if no valid client `for` is
   identified, **nothing** from the header is trusted — not even `host`/`proto`.
   In both branches `getRemoteAddress()` returns an IP or `null`, **never a
   non-IP string** (an obfuscated `for=unknown` yields `null`).

## `X-Forwarded-*` is per-header trust

The nonstandard headers have no element structure, so the same-hop invariant
cannot transfer wholesale:

- **`X-Forwarded-For` + `X-Forwarded-Host`** act as parallel lists (each proxy
  appends), so the host is read at the **same index** as the client `for` entry
  and is skipped entirely without a valid client hop.
- **`X-Forwarded-Proto` / `X-Forwarded-Port`** carry no hop information (real
  proxies overwrite them rather than append), so they are applied **whenever the
  proxy is trusted**, independently of `X-Forwarded-For`. This is deliberate:
  gating them on a valid `for` would break legitimate TLS-terminator-only setups
  (which send just `X-Forwarded-Proto`) while not closing the pass-through risk —
  a forged proto passed through next to a valid `for` would still be accepted.
  This per-header trust matches Symfony's and Laravel's model.

## Configuration and residual risk

`setProxy($proxy, $forwarded = true, $xForwarded = true)` exposes two independent
switches (the DI/NEON facade `proxyHeaders: xForwarded|forwarded|both|none`
translates to them). The PHP default is `both` (BC); the **DI default is
`xForwarded`** (safe out of the box).

The residual risk is unfixable by parsing: deciding the branch by header *presence*
cannot distinguish a header the proxy set from one the client forged and the proxy
passed through. A proxy that manages only `X-Forwarded-For` but forwards a client's
`Forwarded` unchanged would have that forged header trusted — which is exactly why
the DI default ignores `Forwarded`, and why a trusted proxy **should strip
forwarding headers it does not set** (noted by a comment in `getClient`).
