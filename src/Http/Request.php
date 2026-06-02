<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use function array_change_key_case, array_filter, base64_decode, count, explode, func_num_args, in_array, is_array, preg_match, strcasecmp, strlen, strtr;


/**
 * Immutable representation of an HTTP request with access to URL, headers, cookies, uploaded files, and body.
 *
 * @property-read UrlScript $url
 * @property-read array<string,mixed> $query
 * @property-read array<string,mixed> $post
 * @property-read array<string,mixed> $files
 * @property-read array<string,string> $cookies
 * @property-read string $method
 * @property-read array<string,string> $headers
 * @property-read ?UrlImmutable $referer
 * @property-read bool $secured
 * @property-read bool $ajax
 * @property-read ?string $remoteAddress
 * @property-deprecated ?string $remoteHost
 * @property-read ?string $rawBody
 */
class Request implements IRequest
{
	use Nette\SmartObject;

	/** @var array<string, string> */
	private readonly array $headers;

	/** @var (\Closure(): string)|null */
	private readonly ?\Closure $rawBodyCallback;


	/**
	 * @param array<string, string> $headers
	 * @param ?(callable(): string) $rawBodyCallback
	 */
	public function __construct(
		private UrlScript $url,
		/** @var mixed[] */
		private readonly array $post = [],
		/** @var mixed[] */
		private readonly array $files = [],
		/** @var array<string, string> */
		private readonly array $cookies = [],
		array $headers = [],
		private readonly string $method = 'GET',
		private readonly ?string $remoteAddress = null,
		?string $remoteHost = null,
		?callable $rawBodyCallback = null,
	) {
		$this->headers = array_change_key_case($headers);
		$this->rawBodyCallback = $rawBodyCallback ? $rawBodyCallback(...) : null;
	}


	/**
	 * Returns a clone with a different URL.
	 */
	public function withUrl(UrlScript $url): static
	{
		$dolly = clone $this;
		$dolly->url = $url;
		return $dolly;
	}


	/**
	 * Returns the URL of the request.
	 */
	public function getUrl(): UrlScript
	{
		return $this->url;
	}


	/********************* query, post, files & cookies ****************d*g**/


	/**
	 * Returns a URL query parameter, or all parameters as an array if no key is given.
	 */
	public function getQuery(?string $key = null): mixed
	{
		if (func_num_args() === 0) {
			return $this->url->getQueryParameters();
		}

		assert($key !== null);
		return $this->url->getQueryParameter($key);
	}


	/**
	 * Returns a POST parameter, or all POST parameters as an array if no key is given.
	 */
	public function getPost(?string $key = null): mixed
	{
		if (func_num_args() === 0) {
			return $this->post;
		}

		assert($key !== null);
		return $this->post[$key] ?? null;
	}


	/**
	 * Returns the uploaded file for the given key, or null if not present.
	 * Accepts a string key or an array of keys for nested file structures (e.g. ['form', 'avatar']).
	 * @param  string|string[]  $key
	 */
	public function getFile($key): ?FileUpload
	{
		$res = Nette\Utils\Arrays::get($this->files, $key, null);
		return $res instanceof FileUpload ? $res : null;
	}


	/**
	 * Returns the tree of uploaded files, with each leaf being a FileUpload instance.
	 * @return mixed[]
	 */
	public function getFiles(): array
	{
		return $this->files;
	}


	/**
	 * Returns a cookie or `null` if it does not exist.
	 */
	public function getCookie(string $key): ?string
	{
		return $this->cookies[$key] ?? null;
	}


	/**
	 * Returns all cookies.
	 * @return array<string, string>
	 */
	public function getCookies(): array
	{
		return $this->cookies;
	}


	/********************* method & headers ****************d*g**/


	/**
	 * Returns the HTTP method with which the request was made (GET, POST, HEAD, PUT, ...).
	 */
	public function getMethod(): string
	{
		return $this->method;
	}


	/**
	 * Checks the HTTP method with which the request was made. The parameter is case-insensitive.
	 */
	public function isMethod(string $method): bool
	{
		return strcasecmp($this->method, $method) === 0;
	}


	/**
	 * Returns an HTTP header or `null` if it does not exist. The parameter is case-insensitive.
	 */
	public function getHeader(string $header): ?string
	{
		$header = strtolower($header);
		return $this->headers[$header] ?? null;
	}


	/**
	 * Returns all HTTP headers as associative array.
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}


	/**
	 * Returns the referrer URL from the Referer header. Unreliable - clients may omit or spoof it.
	 * @deprecated  use getOrigin()
	 */
	public function getReferer(): ?UrlImmutable
	{
		return isset($this->headers['referer'])
			? new UrlImmutable($this->headers['referer'])
			: null;
	}


	/**
	 * Returns the request origin (scheme + host + port) from the Origin header, or null if absent or invalid.
	 */
	public function getOrigin(): ?UrlImmutable
	{
		$header = $this->headers['origin'] ?? '';
		if (!preg_match('~^[a-z][a-z0-9+.-]*://[^/]+$~i', $header)) {
			return null;
		}
		return new UrlImmutable($header);
	}


	/**
	 * Checks whether the request was sent via a secure channel (HTTPS).
	 */
	public function isSecured(): bool
	{
		return $this->url->getScheme() === 'https';
	}


	/**
	 * Checks whether the request is coming from the same site and was initiated by clicking on a link.
	 */
	public function isSameSite(): bool
	{
		return isset($this->cookies[Helpers::StrictCookieName]);
	}


	/**
	 * Checks whether the request matches the given Sec-Fetch-Site, Sec-Fetch-Dest and Sec-Fetch-User values.
	 * Falls back to the SameSite=Strict cookie in browsers without Sec-Fetch (Safari < 16.4)
	 * @param  FetchSite|list<FetchSite>  $site
	 * @param  FetchDest|list<FetchDest>|null  $dest
	 */
	public function isFrom(
		FetchSite|array $site,
		FetchDest|array|null $dest = null,
		?bool $user = null,
	): bool
	{
		$siteHeader = $this->headers['sec-fetch-site'] ?? null;
		$actualDest = FetchDest::tryFrom($this->headers['sec-fetch-dest'] ?? '');
		$actualUser = ($this->headers['sec-fetch-user'] ?? null) === '?1';
		$site = is_array($site) ? $site : [$site];
		$dest = $dest === null || is_array($dest) ? $dest : [$dest];

		if ($siteHeader === null) { // fallback for browsers without Sec-Fetch (Safari < 16.4)
			return $dest === null
				&& $user === null
				&& isset($this->cookies[Helpers::StrictCookieName])
				&& array_filter($site, fn(FetchSite $s) => $s !== FetchSite::CrossSite) !== [];
		}

		$actualSite = FetchSite::tryFrom($siteHeader);
		return $actualSite !== null
			&& in_array($actualSite, $site, strict: true)
			&& ($dest === null || ($actualDest !== null && in_array($actualDest, $dest, strict: true)))
			&& ($user === null || $user === $actualUser);
	}


	/**
	 * Checks whether the request was made via AJAX (X-Requested-With: XMLHttpRequest).
	 */
	public function isAjax(): bool
	{
		return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
	}


	/**
	 * Returns the IP address of the remote client.
	 */
	public function getRemoteAddress(): ?string
	{
		return $this->remoteAddress;
	}


	#[\Deprecated]
	public function getRemoteHost(): ?string
	{
		trigger_error(__METHOD__ . '() is deprecated.', E_USER_DEPRECATED);
		return null;
	}


	/**
	 * Returns raw content of HTTP request body.
	 */
	public function getRawBody(): ?string
	{
		return $this->rawBodyCallback ? ($this->rawBodyCallback)() : null;
	}


	/**
	 * Returns basic HTTP authentication credentials.
	 * @return array{string, string}|null
	 */
	public function getBasicCredentials(): ?array
	{
		return preg_match(
			'~^Basic (\S+)$~',
			$this->headers['authorization'] ?? '',
			$t,
		)
			&& ($t = base64_decode($t[1], strict: true))
			&& ($t = explode(':', $t, 2))
			&& (count($t) === 2)
			? $t
			: null;
	}


	/**
	 * Returns the most preferred language from the Accept-Language header that matches one of the supported languages,
	 * or null if no match is found.
	 * @param  array<string>  $langs  supported language codes (e.g. ['en', 'cs', 'de'])
	 */
	public function detectLanguage(array $langs): ?string
	{
		$header = $this->getHeader('Accept-Language');
		if (!$header) {
			return null;
		}

		usort($langs, fn($a, $b) => strlen($b) <=> strlen($a)); // more specific first
		$accepted = Helpers::parseQualityList(strtr($header, '_', '-')); // cs_CZ means cs-CZ

		foreach (array_keys($accepted) as $token) {
			foreach ($langs as $lang) {
				$l = strtolower($lang);
				if ($token === '*' || $token === $l || str_starts_with($token, $l . '-')) {
					return $lang;
				}
			}
		}

		return null;
	}
}
