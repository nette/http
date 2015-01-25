<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;


/**
 * URI Syntax (RFC 3986).
 *
 * <pre>
 * scheme  user  password  host  port  basePath   relativeUrl
 *   |      |      |        |      |    |             |
 * /--\   /--\ /------\ /-------\ /--\/--\/----------------------------\
 * http://john:x0y17575@nette.org:8042/en/manual.php?name=param#fragment  <-- absoluteUrl
 *        \__________________________/\____________/^\________/^\______/
 *                     |                     |           |         |
 *                 authority               path        query    fragment
 * </pre>
 *
 * - authority:   [user[:password]@]host[:port]
 * - hostUrl:     http://user:password@nette.org:8042
 * - basePath:    /en/ (everything before relative URI not including the script name)
 * - baseUrl:     http://user:password@nette.org:8042/en/
 * - relativeUrl: manual.php
 *
 * @author     David Grudl
 *
 * @property   string $scheme
 * @property   string $user
 * @property   string $password
 * @property   string $host
 * @property   string $port
 * @property   string $path
 * @property   string $query
 * @property   string $fragment
 * @property-read string $absoluteUrl
 * @property-read string $authority
 * @property-read string $hostUrl
 * @property-read string $basePath
 * @property-read string $baseUrl
 * @property-read string $relativeUrl
 * @property-read array $queryParameters
 */
class Url extends Nette\Object
{
	/** @var array */
	public static $defaultPorts = array(
		'http' => 80,
		'https' => 443,
		'ftp' => 21,
		'news' => 119,
		'nntp' => 119,
	);

	/** @var string */
	private $scheme = '';

	/** @var string */
	private $user = '';

	/** @var string */
	private $password = '';

	/** @var string */
	private $host = '';

	/** @var int */
	private $port;

	/** @var string */
	private $path = '';

	/** @var array */
	private $query = array();

	/** @var string */
	private $fragment = '';


	/**
	 * @param  string|self
	 * @throws Nette\InvalidArgumentException if URL is malformed
	 */
	public function __construct($url = NULL)
	{
		if (is_string($url)) {
			$p = @parse_url($url); // @ - is escalated to exception
			if ($p === FALSE) {
				throw new Nette\InvalidArgumentException("Malformed or unsupported URI '$url'.");
			}

			$this->scheme = isset($p['scheme']) ? $p['scheme'] : '';
			$this->port = isset($p['port']) ? $p['port']
				: (isset(self::$defaultPorts[$this->scheme]) ? self::$defaultPorts[$this->scheme] : NULL);
			$this->host = isset($p['host']) ? rawurldecode($p['host']) : '';
			$this->user = isset($p['user']) ? rawurldecode($p['user']) : '';
			$this->password = isset($p['pass']) ? rawurldecode($p['pass']) : '';
			self::setPathToUrl($this, isset($p['path']) ? $p['path'] : '');
			self::setQueryToUrl($this, isset($p['query']) ? $p['query'] : array());
			$this->fragment = isset($p['fragment']) ? rawurldecode($p['fragment']) : '';

		} elseif ($url instanceof self) {
			foreach ($this as $key => $val) {
				$this->$key = $url->$key;
			}
		}
	}


	/**
	 * Sets the scheme part of URI.
	 * @param  string
	 * @return self
	 */
	public function setScheme($value)
	{
		$clone = clone $this;
		$clone->scheme = (string) $value;
		return $clone;
	}


	/**
	 * Returns the scheme part of URI.
	 * @return string
	 */
	public function getScheme()
	{
		return $this->scheme;
	}


	/**
	 * Sets the user name part of URI.
	 * @param  string
	 * @return self
	 */
	public function setUser($value)
	{
		$clone = clone $this;
		$clone->user = (string) $value;
		return $clone;
	}


	/**
	 * Returns the user name part of URI.
	 * @return string
	 */
	public function getUser()
	{
		return $this->user;
	}


	/**
	 * Sets the password part of URI.
	 * @param  string
	 * @return self
	 */
	public function setPassword($value)
	{
		$clone = clone $this;
		$clone->password = (string) $value;
		return $clone;
	}


	/**
	 * Returns the password part of URI.
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}


	/**
	 * Sets the host part of URI.
	 * @param  string
	 * @return self
	 */
	public function setHost($value)
	{
		$clone = clone $this;
		$clone->host = (string) $value;
		return $clone;
	}


	/**
	 * Returns the host part of URI.
	 * @return string
	 */
	public function getHost()
	{
		return $this->host;
	}


	/**
	 * Sets the port part of URI.
	 * @param  string
	 * @return self
	 */
	public function setPort($value)
	{
		$clone = clone $this;
		$clone->port = (int) $value;
		return $clone;
	}


	/**
	 * Returns the port part of URI.
	 * @return string
	 */
	public function getPort()
	{
		return $this->port;
	}


	/**
	 * Sets the path part of URI.
	 * @param  string
	 * @return self
	 */
	public function setPath($value)
	{
		return self::setPathToUrl(clone $this, $value);
	}


	/**
	 * Sets the path part of URI.
	 * @param Url
	 * @param string
	 * @return self
	 */
	private static function setPathToUrl(Url $url, $value)
	{
		$url->path = (string) $value;
		if (substr($url->path, 0, 1) !== '/' && in_array($url->scheme, array('http', 'https'), TRUE)) {
			$url->path = '/' . $url->path;
		}
		return $url;
	}


	/**
	 * Returns the path part of URI.
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}


	/**
	 * Sets the query part of URI.
	 * @param  string|array
	 * @return self
	 */
	public function setQuery($value)
	{
		return self::setQueryToUrl(clone $this, $value);
	}


	/**
	 * Sets the query part of URI.
	 * @param Url
	 * @param string|array
	 * @return self
	 */
	private function setQueryToUrl(Url $url, $value)
	{
		$url->query = is_array($value) ? $value : self::parseQuery($value);
		return $url;
	}


	/**
	 * Appends the query part of URI.
	 * @param  string|array
	 * @return self
	 */
	public function appendQuery($value)
	{
		$clone = clone $this;
		$clone->query = is_array($value)
			? $value + $this->query
			: self::parseQuery($this->getQuery() . '&' . $value);
		return $clone;
	}


	/**
	 * Returns the query part of URI.
	 * @return string
	 */
	public function getQuery()
	{
		if (PHP_VERSION < 50400) {
			return str_replace('+', '%20', http_build_query($this->query, '', '&'));
		}
		return http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
	}


	/**
	 * @return array
	 */
	public function getQueryParameters()
	{
		return $this->query;
	}


	/**
	 * @param string
	 * @param mixed
	 * @return mixed
	 */
	public function getQueryParameter($name, $default = NULL)
	{
		return isset($this->query[$name]) ? $this->query[$name] : $default;
	}


	/**
	 * @param string
	 * @param mixed NULL unsets the parameter
	 * @return self
	 */
	public function setQueryParameter($name, $value)
	{
		$clone = clone $this;
		$clone->query[$name] = $value;
		return $clone;
	}


	/**
	 * Sets the fragment part of URI.
	 * @param  string
	 * @return self
	 */
	public function setFragment($value)
	{
		$clone = clone $this;
		$clone->fragment = (string) $value;
		return $clone;
	}


	/**
	 * Returns the fragment part of URI.
	 * @return string
	 */
	public function getFragment()
	{
		return $this->fragment;
	}


	/**
	 * Returns the entire URI including query string and fragment.
	 * @return string
	 */
	public function getAbsoluteUrl()
	{
		return $this->getHostUrl() . $this->path
			. ($this->query ? '?' . $this->getQuery() : '')
			. ($this->fragment === '' ? '' : '#' . $this->fragment);
	}


	/**
	 * Returns the [user[:pass]@]host[:port] part of URI.
	 * @return string
	 */
	public function getAuthority()
	{
		$authority = $this->host;
		if ($this->port && (!isset(self::$defaultPorts[$this->scheme]) || $this->port !== self::$defaultPorts[$this->scheme])) {
			$authority .= ':' . $this->port;
		}

		if ($this->user !== '' && $this->scheme !== 'http' && $this->scheme !== 'https') {
			$authority = rawurlencode($this->user) . ($this->password === '' ? '' : ':' . rawurlencode($this->password)) . '@' . $authority;
		}

		return $authority;
	}


	/**
	 * Returns the scheme and authority part of URI.
	 * @return string
	 */
	public function getHostUrl()
	{
		return ($this->scheme ? $this->scheme . ':' : '') . '//' . $this->getAuthority();
	}


	/**
	 * Returns the base-path.
	 * @return string
	 */
	public function getBasePath()
	{
		$pos = strrpos($this->path, '/');
		return $pos === FALSE ? '' : substr($this->path, 0, $pos + 1);
	}


	/**
	 * Returns the base-URI.
	 * @return string
	 */
	public function getBaseUrl()
	{
		return $this->getHostUrl() . $this->getBasePath();
	}


	/**
	 * Returns the relative-URI.
	 * @return string
	 */
	public function getRelativeUrl()
	{
		return (string) substr($this->getAbsoluteUrl(), strlen($this->getBaseUrl()));
	}


	/**
	 * URL comparison.
	 * @param  string|self
	 * @return bool
	 */
	public function isEqual($url)
	{
		$url = new self($url);
		$query = $url->query;
		sort($query);
		$query2 = $this->query;
		sort($query2);
		$http = in_array($this->scheme, array('http', 'https'), TRUE);
		return $url->scheme === $this->scheme
			&& !strcasecmp($url->host, $this->host)
			&& $url->port === $this->port
			&& ($http || $url->user === $this->user)
			&& ($http || $url->password === $this->password)
			&& self::unescape($url->path, '%/') === self::unescape($this->path, '%/')
			&& $query === $query2
			&& $url->fragment === $this->fragment;
	}


	/**
	 * Transforms URL to canonical form.
	 * @return self
	 */
	public function canonicalize()
	{
		$clone = clone $this;
		$clone->path = preg_replace_callback(
			'#[^!$&\'()*+,/:;=@%]+#',
			function($m) { return rawurlencode($m[0]); },
			self::unescape($clone->path, '%/')
		);
		$clone->host = strtolower($clone->host);
		return $clone;
	}


	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->getAbsoluteUrl();
	}


	/**
	 * Similar to rawurldecode, but preserves reserved chars encoded.
	 * @param  string to decode
	 * @param  string reserved characters
	 * @return string
	 */
	public static function unescape($s, $reserved = '%;/?:@&=+$,')
	{
		// reserved (@see RFC 2396) = ";" | "/" | "?" | ":" | "@" | "&" | "=" | "+" | "$" | ","
		// within a path segment, the characters "/", ";", "=", "?" are reserved
		// within a query component, the characters ";", "/", "?", ":", "@", "&", "=", "+", ",", "$" are reserved.
		if ($reserved !== '') {
			$s = preg_replace_callback(
				'#%(' . substr(chunk_split(bin2hex($reserved), 2, '|'), 0, -1) . ')#i',
				function($m) { return '%25' . strtoupper($m[1]); },
				$s
			);
		}
		return rawurldecode($s);
	}


	/**
	 * Parses query string.
	 * @return array
	 */
	public static function parseQuery($s)
	{
		parse_str($s, $res);
		if (get_magic_quotes_gpc()) { // for PHP 5.3
			$res = Helpers::stripSlashes($res);
		}
		return $res;
	}

}
