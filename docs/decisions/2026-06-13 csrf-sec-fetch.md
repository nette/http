# CSRF protection via Sec-Fetch headers instead of tokens and cookies

## Status

Accepted. Implemented in branch `v3.4` (released in 3.4.0): `Request::isFrom()`,
the `FetchSite` and `FetchDest` enums in `src/Http/enums.php`, and a fallback in
`Helpers::initCookie()`.

## Context

CSRF defense in Nette went through two generations:

1. **Synchronizer token** (`$form->addProtection()`): a token generated into the
   session and verified on submission. Stateful (requires a session), opt-in per
   form - forget it on one form and that form is unprotected.
2. **SameSite cookie `_nss`** (formerly `nette-samesite`): a `SameSite=Strict`
   cookie whose presence is checked by `Request::isSameSite()`. More stateless,
   but flawed:
   - it does not distinguish *origin* from *site* - subdomains (typically hosting
     user content) pass the check as "our own" requests;
   - direct navigation (a link from an e-mail, typing a URL) carries the cookie,
     so it looks same-site;
   - it can say nothing about the request type or whether a user initiated it.

Modern browsers meanwhile added the `Sec-Fetch-*` headers, which state the
request context directly: `Sec-Fetch-Site` (`same-origin` / `same-site` /
`cross-site` / `none`), `Sec-Fetch-Dest` (document, image, script, ...) and
`Sec-Fetch-User` (`?1` = the action was initiated by the user). They cannot be
set by JavaScript or extensions and are stateless. The only relevant gap is
Safari < 16.4.

## Decision

- A new method **`Request::isFrom(FetchSite|array $site, FetchDest|array|null
  $dest = null, ?bool $user = null)`** reads the `Sec-Fetch-Site`,
  `Sec-Fetch-Dest` and `Sec-Fetch-User` headers. The values are backed enums
  `FetchSite` and `FetchDest`, so the query reads type-safely: "did the request
  come from my origin and was it user-initiated?" =
  `isFrom(FetchSite::SameOrigin, user: true)`.
- **Fallback for browsers without Sec-Fetch** (Safari < 16.4): if the
  `Sec-Fetch-Site` header is missing, `isFrom()` falls back to the `_nss` cookie.
  The fallback returns true only when the query asks about the site alone (no
  `$dest` and no `$user` - which a cookie cannot determine) and the requested
  values include something other than `CrossSite`.
- **The `_nss` cookie is sent only to browsers without Sec-Fetch** -
  `Helpers::initCookie()` sets it only when the request does not carry a
  `Sec-Fetch-Site` header. Modern browsers no longer receive the cookie.
- **`isSameSite()` is deprecated** and delegates to
  `isFrom([FetchSite::SameSite, FetchSite::SameOrigin])`.
- Automatic protection of forms and signals on top of `isFrom()` is built by
  nette/forms 3.3 and nette/application 3.3; `$form->addProtection()` is no
  longer needed.

## Rejected alternatives

- **Keep the session token as the primary mechanism.** Stateful, opt-in, prone
  to being forgotten; the browser now supplies the same information more reliably
  and without server state.
- **Keep detection via the `_nss` cookie only.** It does not distinguish origin
  from site, treats direct navigation as same-site, and does not scale to queries
  like "did the user initiate it?" / "is the destination a document?".
- **Check the `Origin`/`Referer` headers.** `Origin` is not sent on same-origin
  GET, `Referer` is often suppressed by policies and clients; Sec-Fetch is
  designed exactly for this purpose and covers all requests.

## Consequences (BC)

- **Direct navigation is no longer same-site.** A link from an e-mail or a
  manually entered URL has `Sec-Fetch-Site: none`; previously the present cookie
  let it pass as same-site. Code relying on the old behavior must explicitly add
  `FetchSite::None`.
- `isFrom(FetchSite::SameOrigin)` is stricter than the old cookie check: it
  requires an exact match of scheme, host and port.
- `isSameSite()` keeps working (deprecated), but its semantics change per the
  points above - subdomain attacks can now be excluded by choosing `SameOrigin`.
- Browsers with Sec-Fetch stop receiving the `_nss` cookie; the response is
  cleaner by one Set-Cookie header.
