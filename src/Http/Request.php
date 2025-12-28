<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;
use function array_change_key_case, base64_decode, count, explode, func_num_args, gethostbyaddr, implode, in_array, preg_match, preg_match_all, rsort, strcasecmp, strtr;
use const CASE_LOWER;


/**
 * HttpRequest provides access scheme for request sent via HTTP.
 *
 * @property-read UrlScript $url
 * @property-read array $query
 * @property-read array $post
 * @property-read array $files
 * @property-read array $cookies
 * @property-read string $method
 * @property-read array $headers
 * @property-read UrlImmutable|null $referer
 * @property-read bool $secured
 * @property-read bool $ajax
 * @property-read string|null $remoteAddress
 * @property-read string|null $remoteHost
 * @property-read string|null $rawBody
 */
class Request implements IRequest
{
	use Nette\SmartObject;

	/** @var string[] */
	private readonly array $headers;

	private readonly ?\Closure $rawBodyCallback;


	public function __construct(
		private UrlScript $url,
		private readonly array $post = [],
		private readonly array $files = [],
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
	 * Returns variable provided to the script via URL query ($_GET).
	 * If no key is passed, returns the entire array.
	 */
	public function getQuery(?string $key = null): mixed
	{
		if (func_num_args() === 0) {
			return $this->url->getQueryParameters();
		}

		return $this->url->getQueryParameter($key);
	}


	/**
	 * Returns variable provided to the script via POST method ($_POST).
	 * If no key is passed, returns the entire array.
	 */
	public function getPost(?string $key = null): mixed
	{
		if (func_num_args() === 0) {
			return $this->post;
		}

		return $this->post[$key] ?? null;
	}


	/**
	 * Returns uploaded file.
	 * @param  string|string[]  $key
	 */
	public function getFile($key): ?FileUpload
	{
		$res = Nette\Utils\Arrays::get($this->files, $key, null);
		return $res instanceof FileUpload ? $res : null;
	}


	/**
	 * Returns tree of upload files in a normalized structure, with each leaf an instance of Nette\Http\FileUpload.
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
	 * @return string[]
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}


	/**
	 * What URL did the user come from? Beware, it is not reliable at all.
	 * @deprecated  deprecated in favor of the getOrigin()
	 */
	public function getReferer(): ?UrlImmutable
	{
		return isset($this->headers['referer'])
			? new UrlImmutable($this->headers['referer'])
			: null;
	}


	/**
	 * What origin did the user come from? It contains scheme, hostname and port.
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
	 * Is the request sent via secure channel (https)?
	 */
	public function isSecured(): bool
	{
		return $this->url->getScheme() === 'https';
	}


	/** @deprecated use isFrom(['same-site', 'same-origin']) */
	public function isSameSite(): bool
	{
		return isset($this->cookies[Helpers::StrictCookieName]);
	}


	/**
	 * Checks whether Sec-Fetch headers match the expected values.
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
	 * Is it an AJAX request?
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
			$this->remoteHost = gethostbyaddr($this->remoteAddress);
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
	 * Returns the most preferred language by browser. Uses the `Accept-Language` header. If no match is reached, it returns `null`.
	 * @param  string[]  $langs  supported languages
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
