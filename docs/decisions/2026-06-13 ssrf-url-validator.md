# SSRF protection: UrlValidator and IPAddress

## Status

Accepted. Implemented in branch `v3.4` (released in 3.4.0):
`src/Http/UrlValidator.php` and `src/Http/IPAddress.php`.

## Context

Server-Side Request Forgery (OWASP Top 10) arises when an application fetches a
user-supplied URL without validating the target. The attacker then aims at
internal services: cloud metadata (`169.254.169.254`, which hands out IAM keys),
private networks (`192.168.x.x`), localhost. With the spread of MCP servers and
AI integrations that routinely fetch user-supplied URLs, the attack surface is
growing. Nette offered no support so far, and hand-written checks typically miss
the subtleties: IPv4-mapped IPv6 (`::ffff:127.0.0.1` is loopback), DNS rebinding
(the record changes between validation and fetch), a hostname resolving to
several records of which only some are public.

## Decision

- **`UrlValidator`** - a readonly object; the whole policy is given in the
  constructor. The validator itself fetches nothing, it only answers the question
  "may this address be accessed?".
  - **Strict default:** only `https`, port 443 (the scheme's implicit port is
    honored), no userinfo, public IPs only. Anyone needing more (`http`, private
    networks, loopback, ...) must allow it explicitly. Multicast is always
    rejected, with no opt-in.
  - **Host allowlist/blocklist** with the wildcard `*.example.com` (any subdomain
    depth; the apex does not match - for the apex both forms are given).
  - `allows()` validates including DNS: the hostname is resolved and **every**
    A/AAAA record must pass the IP policy. `allowsWithoutDns()` is a cheap
    pre-filter without DNS.
- **`getResolvedIPs()`** returns the validated IP addresses to pin the connection
  to (`CURLOPT_RESOLVE`) - the fetch then provably goes to addresses that passed
  validation, closing the DNS-rebinding window.
- **`IPAddress`** - an immutable value object for IPv4/IPv6 with address-class
  predicates (`isPrivate()`, `isLoopback()`, `isLinkLocal()`, `isMulticast()`,
  `isReserved()`, `isPublic()`) and `isInRange()` for CIDR. The predicates
  normalize IPv4-mapped IPv6, so `::ffff:127.0.0.1` evaluates as loopback. A
  standalone public class: useful outside SSRF too (custom range checks, logging)
  and it keeps the knowledge of ranges in one place.

## Rejected alternatives

- **A built-in "safe fetch" (HTTP client).** nette/http has no client and is not
  meant to have one; the validator is deliberately just a policy object that
  composes with any client (cURL, Guzzle).
- **Validation by hostname/IP literal only, without DNS.** Trivially bypassed by
  a domain whose A record points into the internal network. DNS resolution is
  therefore part of `allows()`; the no-DNS variant exists only as an explicit
  `allowsWithoutDns()` for the case where pinning is handled by the fetch layer.
- **One valid DNS record is enough.** The attacker controls their zone and can
  return a mix of public and internal addresses; the client then picks. So all
  records must pass, otherwise "invalid" is returned.
- **A permissive default (http as well as https, any port).** A security API
  should fail toward safety; loosening the policy is a deliberate user decision
  in the constructor.
- **Handle HTTP redirects inside the validator too.** A redirect is seen only by
  the HTTP client; a URL validator cannot intercept it. Documented limit: the
  fetch layer must disable redirects or re-validate each target.

## Limits and consequences

- Validation concerns **the target address only**, not the response body.
- Redirects must be handled by the caller (see above); the same applies to
  protocols other than HTTP(S) if someone adds them to `schemes`.
- `allows()` performs a real DNS lookup - it has latency and its result depends
  on the resolver; for a hot path `allowsWithoutDns()` + `getResolvedIPs()` at
  the point of fetch are available.
