<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


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
 * @property-read Url|null $referer
 * @property-read bool $secured
 * @property-read bool $ajax
 * @property-read string|null $remoteAddress
 * @property-read string|null $remoteHost
 * @property-read string|null $rawBody
 */
class Request implements IRequest
{
	use Nette\SmartObject;

	/** @var string */
	private $method;

	/** @var UrlScript */
	private $url;

	/** @var array */
	private $post;

	/** @var array */
	private $files;

	/** @var array */
	private $cookies;

	/** @var array */
	private $headers;

	/** @var string|null */
	private $remoteAddress;

	/** @var string|null */
	private $remoteHost;

	/** @var callable|null */
	private $rawBodyCallback;


	public function __construct(UrlScript $url, array $post = null, array $files = null, array $cookies = null,
		array $headers = null, string $method = null, string $remoteAddress = null, string $remoteHost = null, callable $rawBodyCallback = null)
	{
		$this->url = $url;
		$this->post = (array) $post;
		$this->files = (array) $files;
		$this->cookies = (array) $cookies;
		$this->headers = array_change_key_case((array) $headers, CASE_LOWER);
		$this->method = $method ?: 'GET';
		$this->remoteAddress = $remoteAddress;
		$this->remoteHost = $remoteHost;
		$this->rawBodyCallback = $rawBodyCallback;
	}


	/**
	 * @return static
	 */
	public function withUrl(UrlScript $url)
	{
		$dolly = clone $this;
		$dolly->url = $url;
		return $dolly;
	}


	/**
	 * Returns URL object.
	 */
	public function getUrl(): UrlScript
	{
		return $this->url;
	}


	/********************* query, post, files & cookies ****************d*g**/


	/**
	 * Returns variable provided to the script via URL query ($_GET).
	 * If no key is passed, returns the entire array.
	 * @return mixed
	 */
	public function getQuery(string $key = null)
	{
		if (func_num_args() === 0) {
			return $this->url->getQueryParameters();
		} elseif (func_num_args() > 1) {
			trigger_error(__METHOD__ . '() parameter $default is deprecated, use operator ??', E_USER_DEPRECATED);
		}
		return $this->url->getQueryParameter($key);
	}


	/**
	 * Returns variable provided to the script via POST method ($_POST).
	 * If no key is passed, returns the entire array.
	 * @return mixed
	 */
	public function getPost(string $key = null)
	{
		if (func_num_args() === 0) {
			return $this->post;
		} elseif (func_num_args() > 1) {
			trigger_error(__METHOD__ . '() parameter $default is deprecated, use operator ??', E_USER_DEPRECATED);
		}
		return $this->post[$key] ?? null;
	}


	/**
	 * Returns uploaded file.
	 * @return FileUpload|array|null
	 */
	public function getFile(string $key)
	{
		return $this->files[$key] ?? null;
	}


	/**
	 * Returns uploaded files.
	 */
	public function getFiles(): array
	{
		return $this->files;
	}


	/**
	 * Returns variable provided to the script via HTTP cookies.
	 * @return mixed
	 */
	public function getCookie(string $key)
	{
		if (func_num_args() > 1) {
			trigger_error(__METHOD__ . '() parameter $default is deprecated, use operator ??', E_USER_DEPRECATED);
		}
		return $this->cookies[$key] ?? null;
	}


	/**
	 * Returns variables provided to the script via HTTP cookies.
	 */
	public function getCookies(): array
	{
		return $this->cookies;
	}


	/********************* method & headers ****************d*g**/


	/**
	 * Returns HTTP request method (GET, POST, HEAD, PUT, ...). The method is case-sensitive.
	 */
	public function getMethod(): string
	{
		return $this->method;
	}


	/**
	 * Checks if the request method is the given one.
	 */
	public function isMethod(string $method): bool
	{
		return strcasecmp($this->method, $method) === 0;
	}


	/**
	 * Return the value of the HTTP header. Pass the header name as the
	 * plain, HTTP-specified header name (e.g. 'Accept-Encoding').
	 */
	public function getHeader(string $header): ?string
	{
		if (func_num_args() > 1) {
			trigger_error(__METHOD__ . '() parameter $default is deprecated, use operator ??', E_USER_DEPRECATED);
		}
		$header = strtolower($header);
		return $this->headers[$header] ?? null;
	}


	/**
	 * Returns all HTTP headers.
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}


	/**
	 * Returns referrer.
	 */
	public function getReferer(): ?UrlImmutable
	{
		return isset($this->headers['referer'])
			? new UrlImmutable($this->headers['referer'])
			: null;
	}


	/**
	 * Is the request sent via secure channel (https)?
	 */
	public function isSecured(): bool
	{
		return $this->url->getScheme() === 'https';
	}


	/**
	 * Is the request sent from the same origin?
	 */
	public function isSameSite(): bool
	{
		return isset($this->cookies['nette-samesite']);
	}


	/**
	 * Is AJAX request?
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
	 * Parse Accept-Language header and returns preferred language.
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
