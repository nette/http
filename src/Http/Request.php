<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use function array_change_key_case, base64_decode, count, explode, gethostbyaddr, implode, in_array, preg_match, preg_match_all, rsort, strcasecmp, strtr;


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
 * @property-read ?string $remoteHost
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
		private ?string $remoteHost = null,
		?callable $rawBodyCallback = null,
	) {
		$this->headers = array_change_key_case($headers, CASE_LOWER);
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
		if ($key === null) {
			return $this->url->getQueryParameters();
		}

		return $this->url->getQueryParameter($key);
	}


	/**
	 * Returns a POST parameter, or all POST parameters as an array if no key is given.
	 */
	public function getPost(?string $key = null): mixed
	{
		if ($key === null) {
			return $this->post;
		}

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
	public function getCookie(string $key): mixed
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
	 * Checks whether the request origin and initiator match the given Sec-Fetch-Site and Sec-Fetch-Dest values.
	 * Falls back to the Origin header for browsers that don't send Sec-Fetch headers (Safari < 16.4).
	 * @param  string|list<string>|null  $site       expected Sec-Fetch-Site values (e.g. 'same-origin', 'cross-site')
	 * @param  string|list<string>|null  $initiator  expected Sec-Fetch-Dest values (e.g. 'document', 'empty')
	 */
	public function isFrom(string|array|null $site = null, string|array|null $initiator = null): bool
	{
		$actualSite = $this->headers['sec-fetch-site'] ?? null;
		$actualDest = $this->headers['sec-fetch-dest'] ?? null;

		if ($actualSite === null && ($origin = $this->getOrigin())) { // fallback for Safari < 16.4
			$actualSite = strcasecmp($origin->getScheme(), $this->url->getScheme()) === 0
					&& strcasecmp(rtrim($origin->getHost(), '.'), rtrim($this->url->getHost(), '.')) === 0
					&& $origin->getPort() === $this->url->getPort()
				? 'same-origin'
				: 'cross-site';
		}

		return ($site === null || ($actualSite !== null && in_array($actualSite, (array) $site, strict: true)))
			&& ($initiator === null || ($actualDest !== null && in_array($actualDest, (array) $initiator, strict: true)));
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


	/**
	 * Returns the host of the remote client.
	 */
	public function getRemoteHost(): ?string
	{
		if ($this->remoteHost === null && $this->remoteAddress !== null) {
			$this->remoteHost = gethostbyaddr($this->remoteAddress) ?: null;
		}

		return $this->remoteHost;
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

		$s = strtolower($header);  // case insensitive
		$s = strtr($s, '_', '-');  // cs_CZ means cs-CZ
		rsort($langs);             // first more specific
		preg_match_all('#(' . implode('|', $langs) . ')(?:-[^\s,;=]+)?\s*(?:;\s*q=([0-9.]+))?#', $s, $matches);

		if (!$matches[0]) {
			return null;
		}

		$max = 0;
		$lang = null;
		foreach ($matches[1] as $key => $value) {
			$q = $matches[2][$key] === '' ? 1.0 : (float) $matches[2][$key];
			if ($q > $max) {
				$max = $q;
				$lang = $value;
			}
		}

		return $lang;
	}
}
