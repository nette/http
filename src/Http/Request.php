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
 * @property-read Url|NULL $referer
 * @property-read bool $secured
 * @property-read bool $ajax
 * @property-read string|NULL $remoteAddress
 * @property-read string|NULL $remoteHost
 * @property-read string|NULL $rawBody
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

	/** @var string|NULL */
	private $remoteAddress;

	/** @var string|NULL */
	private $remoteHost;

	/** @var callable|NULL */
	private $rawBodyCallback;


	public function __construct(UrlScript $url, $query = NULL, $post = NULL, $files = NULL, $cookies = NULL,
		$headers = NULL, $method = NULL, $remoteAddress = NULL, $remoteHost = NULL, $rawBodyCallback = NULL)
	{
		$this->url = $url;
		if ($query !== NULL) {
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
	 * @return mixed
	 */
	public function getQuery($key = NULL)
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
	 * @param  string key
	 * @return mixed
	 */
	public function getPost($key = NULL)
	{
		if (func_num_args() === 0) {
			return $this->post;
		} elseif (func_num_args() > 1) {
			trigger_error(__METHOD__ . '() parameter $default is deprecated, use operator ??', E_USER_DEPRECATED);
		}
		return $this->post[$key] ?? NULL;
	}


	/**
	 * Returns uploaded file.
	 * @param  string key
	 * @return FileUpload|array|NULL
	 */
	public function getFile($key)
	{
		return $this->files[$key] ?? NULL;
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
	 * @return mixed
	 */
	public function getCookie($key)
	{
		if (func_num_args() > 1) {
			trigger_error(__METHOD__ . '() parameter $default is deprecated, use operator ??', E_USER_DEPRECATED);
		}
		return $this->cookies[$key] ?? NULL;
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
	 * Return the value of the HTTP header. Pass the header name as the
	 * plain, HTTP-specified header name (e.g. 'Accept-Encoding').
	 * @param  string
	 * @return string|NULL
	 */
	public function getHeader($header)
	{
		if (func_num_args() > 1) {
			trigger_error(__METHOD__ . '() parameter $default is deprecated, use operator ??', E_USER_DEPRECATED);
		}
		$header = strtolower($header);
		return $this->headers[$header] ?? NULL;
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
	 * @return Url|NULL
	 */
	public function getReferer()
	{
		return isset($this->headers['referer']) ? new Url($this->headers['referer']) : NULL;
	}


	/**
	 * Is the request is sent via secure channel (https).
	 * @return bool
	 */
	public function isSecured()
	{
		return $this->url->getScheme() === 'https';
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
	 * @return string|NULL
	 */
	public function getRemoteAddress()
	{
		return $this->remoteAddress;
	}


	/**
	 * Returns the host of the remote client.
	 * @return string|NULL
	 */
	public function getRemoteHost()
	{
		if ($this->remoteHost === NULL && $this->remoteAddress !== NULL) {
			$this->remoteHost = getHostByAddr($this->remoteAddress);
		}
		return $this->remoteHost;
	}


	/**
	 * Returns raw content of HTTP request body.
	 * @return string|NULL
	 */
	public function getRawBody()
	{
		return $this->rawBodyCallback ? ($this->rawBodyCallback)() : NULL;
	}


	/**
	 * Parse Accept-Language header and returns preferred language.
	 * @param  string[] supported languages
	 * @return string|NULL
	 */
	public function detectLanguage(array $langs)
	{
		$header = $this->getHeader('Accept-Language');
		if (!$header) {
			return NULL;
		}

		$s = strtolower($header);  // case insensitive
		$s = strtr($s, '_', '-');  // cs_CZ means cs-CZ
		rsort($langs);             // first more specific
		preg_match_all('#(' . implode('|', $langs) . ')(?:-[^\s,;=]+)?\s*(?:;\s*q=([0-9.]+))?#', $s, $matches);

		if (!$matches[0]) {
			return NULL;
		}

		$max = 0;
		$lang = NULL;
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
