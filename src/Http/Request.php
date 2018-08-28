<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

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


	public function __construct(UrlScript $url, $query = null, $post = null, $files = null, $cookies = null,
		$headers = null, $method = null, $remoteAddress = null, $remoteHost = null, $rawBodyCallback = null)
	{
		$this->url = $url;
		if ($query !== null) {
			trigger_error('Nette\Http\Request::__construct(): parameter $query is deprecated.', E_USER_DEPRECATED);
			$url->setQuery($query);
		}
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
	 * Returns URL object.
	 * @return UrlScript
	 */
	public function getUrl()
	{
		return clone $this->url;
	}


	/********************* query, post, files & cookies ****************d*g**/


	/**
	 * Returns variable provided to the script via URL query ($_GET).
	 * If no key is passed, returns the entire array.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	public function getQuery($key = null, $default = null)
	{
		if (func_num_args() === 0) {
			return $this->url->getQueryParameters();
		} else {
			return $this->url->getQueryParameter($key, $default);
		}
	}


	/**
	 * Returns variable provided to the script via POST method ($_POST).
	 * If no key is passed, returns the entire array.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	public function getPost($key = null, $default = null)
	{
		if (func_num_args() === 0) {
			return $this->post;

		} elseif (isset($this->post[$key])) {
			return $this->post[$key];

		} else {
			return $default;
		}
	}


	/**
	 * Returns uploaded file.
	 * @param  string key
	 * @return FileUpload|array|null
	 */
	public function getFile($key)
	{
		return isset($this->files[$key]) ? $this->files[$key] : null;
	}


	/**
	 * Returns uploaded files.
	 * @return array
	 */
	public function getFiles()
	{
		return $this->files;
	}


	/**
	 * Returns variable provided to the script via HTTP cookies.
	 * @param  string key
	 * @param  mixed  default value
	 * @return mixed
	 */
	public function getCookie($key, $default = null)
	{
		return isset($this->cookies[$key]) ? $this->cookies[$key] : $default;
	}


	/**
	 * Returns variables provided to the script via HTTP cookies.
	 * @return array
	 */
	public function getCookies()
	{
		return $this->cookies;
	}


	/********************* method & headers ****************d*g**/


	/**
	 * Returns HTTP request method (GET, POST, HEAD, PUT, ...). The method is case-sensitive.
	 * @return string
	 */
	public function getMethod()
	{
		return $this->method;
	}


	/**
	 * Checks if the request method is the given one.
	 * @param  string
	 * @return bool
	 */
	public function isMethod($method)
	{
		return strcasecmp($this->method, $method) === 0;
	}


	/**
	 * @deprecated
	 */
	public function isPost()
	{
		trigger_error('Method isPost() is deprecated, use isMethod(\'POST\') instead.', E_USER_DEPRECATED);
		return $this->isMethod('POST');
	}


	/**
	 * Return the value of the HTTP header. Pass the header name as the
	 * plain, HTTP-specified header name (e.g. 'Accept-Encoding').
	 * @param  string
	 * @param  string|null
	 * @return string|null
	 */
	public function getHeader($header, $default = null)
	{
		$header = strtolower($header);
		return isset($this->headers[$header]) ? $this->headers[$header] : $default;
	}


	/**
	 * Returns all HTTP headers.
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}


	/**
	 * Returns referrer.
	 * @return Url|null
	 */
	public function getReferer()
	{
		return isset($this->headers['referer']) ? new Url($this->headers['referer']) : null;
	}


	/**
	 * Is the request sent via secure channel (https)?
	 * @return bool
	 */
	public function isSecured()
	{
		return $this->url->getScheme() === 'https';
	}


	/**
	 * Is the request sent from the same origin?
	 * @return bool
	 */
	public function isSameSite()
	{
		return isset($this->cookies['nette-samesite']);
	}


	/**
	 * Is AJAX request?
	 * @return bool
	 */
	public function isAjax()
	{
		return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
	}


	/**
	 * Returns the IP address of the remote client.
	 * @return string|null
	 */
	public function getRemoteAddress()
	{
		return $this->remoteAddress;
	}


	/**
	 * Returns the host of the remote client.
	 * @return string|null
	 */
	public function getRemoteHost()
	{
		if ($this->remoteHost === null && $this->remoteAddress !== null) {
			$this->remoteHost = gethostbyaddr($this->remoteAddress);
		}
		return $this->remoteHost;
	}


	/**
	 * Returns raw content of HTTP request body.
	 * @return string|null
	 */
	public function getRawBody()
	{
		return $this->rawBodyCallback ? call_user_func($this->rawBodyCallback) : null;
	}


	/**
	 * Parse Accept-Language header and returns preferred language.
	 * @param  string[] supported languages
	 * @return string|null
	 */
	public function detectLanguage(array $langs)
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
