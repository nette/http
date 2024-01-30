<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


/**
 * Mutable representation of a URL.
 *
 * <pre>
 * scheme  user  password  host  port      path        query    fragment
 *   |      |      |        |      |        |            |         |
 * /--\   /--\ /------\ /-------\ /--\/------------\ /--------\ /------\
 * http://john:x0y17575@nette.org:8042/en/manual.php?name=param#fragment  <-- absoluteUrl
 * \______\__________________________/
 *     |               |
 *  hostUrl        authority
 * </pre>
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
	 * @param  string|self|UrlImmutable  $url
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
			$this->host = rawurldecode($p['host'] ?? '');
			$this->user = rawurldecode($p['user'] ?? '');
			$this->password = rawurldecode($p['pass'] ?? '');
			$this->setPath($p['path'] ?? '');
			$this->setQuery($p['query'] ?? []);
			$this->fragment = rawurldecode($p['fragment'] ?? '');

		} elseif ($url instanceof UrlImmutable || $url instanceof self) {
			[$this->scheme, $this->user, $this->password, $this->host, $this->port, $this->path, $this->query, $this->fragment] = $url->export();

		} elseif ($url !== null) {
			throw new Nette\InvalidArgumentException;
		}
	}


	/** @return static */
	public function setScheme(string $scheme)
	{
		$this->scheme = $scheme;
		return $this;
	}


	public function getScheme(): string
	{
		return $this->scheme;
	}


	/** @return static */
	public function setUser(string $user)
	{
		$this->user = $user;
		return $this;
	}


	public function getUser(): string
	{
		return $this->user;
	}


	/** @return static */
	public function setPassword(string $password)
	{
		$this->password = $password;
		return $this;
	}


	public function getPassword(): string
	{
		return $this->password;
	}


	/** @return static */
	public function setHost(string $host)
	{
		$this->host = $host;
		$this->setPath($this->path);
		return $this;
	}


	public function getHost(): string
	{
		return $this->host;
	}


	/**
	 * Returns the part of domain.
	 */
	public function getDomain(int $level = 2): string
	{
		$parts = ip2long($this->host)
			? [$this->host]
			: explode('.', $this->host);
		$parts = $level >= 0
			? array_slice($parts, -$level)
			: array_slice($parts, 0, $level);
		return implode('.', $parts);
	}


	/** @return static */
	public function setPort(int $port)
	{
		$this->port = $port;
		return $this;
	}


	public function getPort(): ?int
	{
		return $this->port ?: $this->getDefaultPort();
	}


	public function getDefaultPort(): ?int
	{
		return self::$defaultPorts[$this->scheme] ?? null;
	}


	/** @return static */
	public function setPath(string $path)
	{
		$this->path = $path;
		if ($this->host && substr($this->path, 0, 1) !== '/') {
			$this->path = '/' . $this->path;
		}

		return $this;
	}


	public function getPath(): string
	{
		return $this->path;
	}


	/**
	 * @param  string|array  $value
	 * @return static
	 */
	public function setQuery($query)
	{
		$this->query = is_array($query) ? $query : self::parseQuery($query);
		return $this;
	}


	/**
	 * @param  string|array  $value
	 * @return static
	 */
	public function appendQuery($query)
	{
		$this->query = is_array($query)
			? $query + $this->query
			: self::parseQuery($this->getQuery() . '&' . $query);
		return $this;
	}


	public function getQuery(): string
	{
		return http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
	}


	public function getQueryParameters(): array
	{
		return $this->query;
	}


	/** @return mixed */
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


	/** @return static */
	public function setFragment(string $fragment)
	{
		$this->fragment = $fragment;
		return $this;
	}


	public function getFragment(): string
	{
		return $this->fragment;
	}


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
			: ($this->user !== ''
				? rawurlencode($this->user) . ($this->password === '' ? '' : ':' . rawurlencode($this->password)) . '@'
				: '')
			. $this->host
			. ($this->port && $this->port !== $this->getDefaultPort()
				? ':' . $this->port
				: '');
	}


	/**
	 * Returns the scheme and authority part of URI.
	 */
	public function getHostUrl(): string
	{
		return ($this->scheme ? $this->scheme . ':' : '')
			. (($authority = $this->getAuthority()) !== '' ? '//' . $authority : '');
	}


	/** @deprecated use UrlScript::getBasePath() instead */
	public function getBasePath(): string
	{
		$pos = strrpos($this->path, '/');
		return $pos === false ? '' : substr($this->path, 0, $pos + 1);
	}


	/** @deprecated use UrlScript::getBaseUrl() instead */
	public function getBaseUrl(): string
	{
		return $this->getHostUrl() . $this->getBasePath();
	}


	/** @deprecated use UrlScript::getRelativeUrl() instead */
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
		$host = rtrim($url->host, '.');
		$host2 = rtrim($this->host, '.');
		return $url->scheme === $this->scheme
			&& (!strcasecmp($host, $host2)
				|| self::idnHostToUnicode($host) === self::idnHostToUnicode($host2))
			&& $url->getPort() === $this->getPort()
			&& $url->user === $this->user
			&& $url->password === $this->password
			&& self::unescape($url->path, '%/') === self::unescape($this->path, '%/')
			&& $query === $query2
			&& $url->fragment === $this->fragment;
	}


	/**
	 * Transforms URL to canonical form.
	 * @return static
	 * @deprecated
	 */
	public function canonicalize()
	{
		$this->path = preg_replace_callback(
			'#[^!$&\'()*+,/:;=@%]+#',
			function (array $m): string { return rawurlencode($m[0]); },
			self::unescape($this->path, '%/')
		);
		$this->host = rtrim($this->host, '.');
		$this->host = self::idnHostToUnicode(strtolower($this->host));
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


	/** @internal */
	final public function export(): array
	{
		return [$this->scheme, $this->user, $this->password, $this->host, $this->port, $this->path, $this->query, $this->fragment];
	}


	/**
	 * Converts IDN ASCII host to UTF-8.
	 */
	private static function idnHostToUnicode(string $host): string
	{
		if (strpos($host, '--') === false) { // host does not contain IDN
			return $host;
		}

		if (function_exists('idn_to_utf8') && defined('INTL_IDNA_VARIANT_UTS46')) {
			return idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
		}

		trigger_error('PHP extension intl is not loaded or is too old', E_USER_WARNING);
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
				function (array $m): string { return '%25' . strtoupper($m[1]); },
				$s
			);
		}

		return rawurldecode($s);
	}


	/**
	 * Parses query string. Is affected by directive arg_separator.input.
	 */
	public static function parseQuery(string $s): array
	{
		$s = str_replace(['%5B', '%5b'], '[', $s);
		$sep = preg_quote(ini_get('arg_separator.input'));
		$s = preg_replace("#([$sep])([^[$sep=]+)([^$sep]*)#", '&0[$2]$3', '&' . $s);
		parse_str($s, $res);
		return $res[0] ?? [];
	}
}
