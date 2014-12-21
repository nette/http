<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

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
 * @property   string $scheme
 * @property   string $user
 * @property   string $password
 * @property   string $host
 * @property   int $port
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
class Url implements \JsonSerializable
{
	use Nette\SmartObject;

	/** @var array */
	public static $defaultPorts = [
		'http' => 80,
		'https' => 443,
		'ftp' => 21,
		'news' => 119,
		'nntp' => 119,
	];

	/** @var string */
	private $scheme = '';

	/** @var string */
	private $user = '';

	/** @var string */
	private $password = '';

	/** @var string */
	private $host = '';

	/** @var int|null */
	private $port;

	/** @var string */
	private $path = '';

	/** @var array */
	private $query = [];

	/** @var string */
	private $fragment = '';


	/**
	 * @param  string|self  $url
	 * @throws Nette\InvalidArgumentException if URL is malformed
	 */
	public function __construct($url = null)
	{
		if (is_string($url)) {
			$p = @parse_url($url); // @ - is escalated to exception
			if ($p === false) {
				throw new Nette\InvalidArgumentException("Malformed or unsupported URI '$url'.");
			}

			$this->scheme = $p['scheme'] ?? '';
			$this->port = $p['port'] ?? null;
			$this->host = isset($p['host']) ? rawurldecode($p['host']) : '';
			$this->user = isset($p['user']) ? rawurldecode($p['user']) : '';
			$this->password = isset($p['pass']) ? rawurldecode($p['pass']) : '';
			$this->setPath($p['path'] ?? '');
			$this->setQuery($p['query'] ?? []);
			$this->fragment = isset($p['fragment']) ? rawurldecode($p['fragment']) : '';

		} elseif ($url instanceof self) {
			foreach ($this as $key => $val) {
				$this->$key = $url->$key;
			}
		}
	}


	/**
	 * Sets the scheme part of URI.
	 * @return static
	 */
	public function setScheme(string $value)
	{
		$this->scheme = $value;
		return $this;
	}


	/**
	 * Returns the scheme part of URI.
	 */
	public function getScheme(): string
	{
		return $this->scheme;
	}


	/**
	 * Sets the user name part of URI.
	 * @return static
	 */
	public function setUser(string $value)
	{
		$this->user = $value;
		return $this;
	}


	/**
	 * Returns the user name part of URI.
	 */
	public function getUser(): string
	{
		return $this->user;
	}


	/**
	 * Sets the password part of URI.
	 * @return static
	 */
	public function setPassword(string $value)
	{
		$this->password = $value;
		return $this;
	}


	/**
	 * Returns the password part of URI.
	 */
	public function getPassword(): string
	{
		return $this->password;
	}


	/**
	 * Sets the host part of URI.
	 * @return static
	 */
	public function setHost(string $value)
	{
		$this->host = $value;
		$this->setPath($this->path);
		return $this;
	}


	/**
	 * Returns the host part of URI.
	 */
	public function getHost(): string
	{
		return $this->host;
	}


	/**
	 * Returns the part of domain.
	 */
	public function getDomain(int $level = 2): string
	{
		$parts = ip2long($this->host) ? [$this->host] : explode('.', $this->host);
		$parts = $level >= 0 ? array_slice($parts, -$level) : array_slice($parts, 0, $level);
		return implode('.', $parts);
	}


	/**
	 * Sets the port part of URI.
	 * @return static
	 */
	public function setPort(int $value)
	{
		$this->port = $value;
		return $this;
	}


	/**
	 * Returns the port part of URI.
	 */
	public function getPort(): ?int
	{
		return $this->port
			? $this->port
			: (self::$defaultPorts[$this->scheme] ?? null);
	}


	/**
	 * Sets the path part of URI.
	 * @return static
	 */
	public function setPath(string $value)
	{
		$this->path = $value;
		if ($this->host && substr($this->path, 0, 1) !== '/') {
			$this->path = '/' . $this->path;
		}
		return $this;
	}


	/**
	 * Returns the path part of URI.
	 */
	public function getPath(): string
	{
		return $this->path;
	}


	/**
	 * Sets the query part of URI.
	 * @param  string|array  $value
	 * @return static
	 */
	public function setQuery($value)
	{
		$this->query = is_array($value) ? $value : self::parseQuery($value);
		return $this;
	}


	/**
	 * Appends the query part of URI.
	 * @param  string|array  $value
	 * @return static
	 */
	public function appendQuery($value)
	{
		$this->query = is_array($value)
			? $value + $this->query
			: self::parseQuery($this->getQuery() . '&' . $value);
		return $this;
	}


	/**
	 * Returns the query part of URI.
	 */
	public function getQuery(): string
	{
		return http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
	}


	public function getQueryParameters(): array
	{
		return $this->query;
	}


	/**
	 * @return mixed
	 */
	public function getQueryParameter(string $name)
	{
		if (func_num_args() > 1) {
			trigger_error(__METHOD__ . '() parameter $default is deprecated, use operator ??', E_USER_DEPRECATED);
		}
		return $this->query[$name] ?? null;
	}


	/**
	 * @param mixed  $value  null unsets the parameter
	 * @return static
	 */
	public function setQueryParameter(string $name, $value)
	{
		$this->query[$name] = $value;
		return $this;
	}


	/**
	 * Sets the fragment part of URI.
	 * @return static
	 */
	public function setFragment(string $value)
	{
		$this->fragment = $value;
		return $this;
	}


	/**
	 * Returns the fragment part of URI.
	 */
	public function getFragment(): string
	{
		return $this->fragment;
	}


	/**
	 * Returns the entire URI including query string and fragment.
	 */
	public function getAbsoluteUrl(): string
	{
		return $this->getHostUrl() . $this->path
			. (($tmp = $this->getQuery()) ? '?' . $tmp : '')
			. ($this->fragment === '' ? '' : '#' . $this->fragment);
	}


	/**
	 * Returns the [user[:pass]@]host[:port] part of URI.
	 */
	public function getAuthority(): string
	{
		return $this->host === ''
			? ''
			: ($this->user !== '' && $this->scheme !== 'http' && $this->scheme !== 'https'
				? rawurlencode($this->user) . ($this->password === '' ? '' : ':' . rawurlencode($this->password)) . '@'
				: '')
			. $this->host
			. ($this->port && (!isset(self::$defaultPorts[$this->scheme]) || $this->port !== self::$defaultPorts[$this->scheme])
				? ':' . $this->port
				: '');
	}


	/**
	 * Returns the scheme and authority part of URI.
	 */
	public function getHostUrl(): string
	{
		return ($this->scheme ? $this->scheme . ':' : '')
			. (($authority = $this->getAuthority()) || $this->scheme ? '//' . $authority : '');
	}


	/**
	 * Returns the base-path.
	 */
	public function getBasePath(): string
	{
		$pos = strrpos($this->path, '/');
		return $pos === false ? '' : substr($this->path, 0, $pos + 1);
	}


	/**
	 * Returns the base-URI.
	 */
	public function getBaseUrl(): string
	{
		return $this->getHostUrl() . $this->getBasePath();
	}


	/**
	 * Returns the relative-URI.
	 */
	public function getRelativeUrl(): string
	{
		return substr($this->getAbsoluteUrl(), strlen($this->getBaseUrl()));
	}


	/**
	 * URL comparison.
	 * @param  string|self  $url
	 */
	public function isEqual($url): bool
	{
		$url = new self($url);
		$query = $url->query;
		ksort($query);
		$query2 = $this->query;
		ksort($query2);
		$http = in_array($this->scheme, ['http', 'https'], true);
		return $url->scheme === $this->scheme
			&& !strcasecmp($url->host, $this->host)
			&& $url->getPort() === $this->getPort()
			&& ($http || $url->user === $this->user)
			&& ($http || $url->password === $this->password)
			&& self::unescape($url->path, '%/') === self::unescape($this->path, '%/')
			&& $query === $query2
			&& $url->fragment === $this->fragment;
	}


	/**
	 * Transforms URL to canonical form.
	 * @return static
	 */
	public function canonicalize()
	{
		$this->path = preg_replace_callback(
			'#[^!$&\'()*+,/:;=@%]+#',
			function ($m) { return rawurlencode($m[0]); },
			self::unescape($this->path, '%/')
		);
		$this->host = strtolower($this->host);
		return $this;
	}


	public function __toString(): string
	{
		return $this->getAbsoluteUrl();
	}


	public function jsonSerialize(): string
	{
		return $this->getAbsoluteUrl();
	}


	/**
	 * Similar to rawurldecode, but preserves reserved chars encoded.
	 */
	public static function unescape(string $s, string $reserved = '%;/?:@&=+$,'): string
	{
		// reserved (@see RFC 2396) = ";" | "/" | "?" | ":" | "@" | "&" | "=" | "+" | "$" | ","
		// within a path segment, the characters "/", ";", "=", "?" are reserved
		// within a query component, the characters ";", "/", "?", ":", "@", "&", "=", "+", ",", "$" are reserved.
		if ($reserved !== '') {
			$s = preg_replace_callback(
				'#%(' . substr(chunk_split(bin2hex($reserved), 2, '|'), 0, -1) . ')#i',
				function ($m) { return '%25' . strtoupper($m[1]); },
				$s
			);
		}
		return rawurldecode($s);
	}


	/**
	 * Parses query string.
	 */
	public static function parseQuery(string $s): array
	{
		$s = str_replace(['%5B', '%5b'], '[', $s);
		$s = preg_replace('#&([^[&=]+)([^&]*)#', '&0[$1]$2', '&' . $s);
		parse_str($s, $res);
		return $res ? $res[0] : [];
	}
}
