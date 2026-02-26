<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use function array_pop, array_slice, bin2hex, chunk_split, defined, explode, function_exists, http_build_query, idn_to_utf8, implode, ini_get, ip2long, is_array, is_string, ksort, parse_str, parse_url, preg_match, preg_quote, preg_replace, preg_replace_callback, rawurldecode, rawurlencode, rtrim, str_contains, str_replace, str_starts_with, strcasecmp, strlen, strrpos, strtolower, strtoupper, substr;
use const PHP_QUERY_RFC3986;


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
 * @property-read array<string,mixed> $queryParameters
 */
class Url implements \JsonSerializable
{
	use Nette\SmartObject;

	/** @var array<string, int> */
	public static array $defaultPorts = [
		'http' => 80,
		'https' => 443,
		'ftp' => 21,
	];

	private string $scheme = '';
	private string $user = '';
	private string $password = '';
	private string $host = '';
	private ?int $port = null;
	private string $path = '';

	/** @var mixed[] */
	private array $query = [];
	private string $fragment = '';


	/**
	 * @throws Nette\InvalidArgumentException if URL is malformed
	 */
	public function __construct(string|self|UrlImmutable|null $url = null)
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
		}
	}


	public function setScheme(string $scheme): static
	{
		$this->scheme = $scheme;
		return $this;
	}


	public function getScheme(): string
	{
		return $this->scheme;
	}


	/** @deprecated */
	public function setUser(string $user): static
	{
		$this->user = $user;
		return $this;
	}


	/** @deprecated */
	public function getUser(): string
	{
		return $this->user;
	}


	/** @deprecated */
	public function setPassword(string $password): static
	{
		$this->password = $password;
		return $this;
	}


	/** @deprecated */
	public function getPassword(): string
	{
		return $this->password;
	}


	public function setHost(string $host): static
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
	 * Returns the specified number of rightmost domain labels (e.g. level 2 of 'www.nette.org' -> 'nette.org').
	 * Negative values trim from the right instead.
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


	public function setPort(int $port): static
	{
		$this->port = $port;
		return $this;
	}


	/**
	 * Returns the port number, falling back to the default port for the scheme if not explicitly set.
	 */
	public function getPort(): ?int
	{
		return $this->port ?: $this->getDefaultPort();
	}


	/**
	 * Returns the default port for the current scheme, or null if the scheme is not recognized.
	 */
	public function getDefaultPort(): ?int
	{
		return self::$defaultPorts[$this->scheme] ?? null;
	}


	/**
	 * Sets the path. Automatically prepends a leading slash when a host is set.
	 */
	public function setPath(string $path): static
	{
		$this->path = $path;
		if ($this->host && !str_starts_with($this->path, '/')) {
			$this->path = '/' . $this->path;
		}

		return $this;
	}


	public function getPath(): string
	{
		return $this->path;
	}


	/** @param string|mixed[] $query */
	public function setQuery(string|array $query): static
	{
		$this->query = is_array($query) ? $query : self::parseQuery($query);
		return $this;
	}


	/**
	 * Merges query parameters into the existing query. Array values use union (existing keys are preserved);
	 * string values are appended and reparsed.
	 * @param string|mixed[] $query
	 */
	public function appendQuery(string|array $query): static
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


	/** @return mixed[] */
	public function getQueryParameters(): array
	{
		return $this->query;
	}


	public function getQueryParameter(string $name): mixed
	{
		return $this->query[$name] ?? null;
	}


	public function setQueryParameter(string $name, mixed $value): static
	{
		$this->query[$name] = $value;
		return $this;
	}


	public function setFragment(string $fragment): static
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
	 * Checks whether two URLs are equal, ignoring query parameter order and trailing dots in hostnames.
	 */
	public function isEqual(string|self|UrlImmutable $url): bool
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
	 * Normalizes the URL to canonical form: percent-encodes path, lowercases and trims the host,
	 * and converts IDN ASCII to Unicode.
	 */
	public function canonicalize(): static
	{
		$this->path = preg_replace_callback(
			'#[^!$&\'()*+,/:;=@%"]+#',
			fn(array $m): string => rawurlencode($m[0]),
			self::unescape($this->path, '%/'),
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
		if (!str_contains($host, '--')) { // host does not contain IDN
			return $host;
		}

		if (function_exists('idn_to_utf8') && defined('INTL_IDNA_VARIANT_UTS46')) {
			return idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: $host;
		}

		trigger_error('PHP extension intl is not loaded or is too old', E_USER_WARNING);
		return $host;
	}


	/**
	 * Decodes percent-encoded characters, but keeps reserved characters (specified in $reserved) encoded.
	 */
	public static function unescape(string $s, string $reserved = '%;/?:@&=+$,'): string
	{
		// reserved (@see RFC 2396) = ";" | "/" | "?" | ":" | "@" | "&" | "=" | "+" | "$" | ","
		// within a path segment, the characters "/", ";", "=", "?" are reserved
		// within a query component, the characters ";", "/", "?", ":", "@", "&", "=", "+", ",", "$" are reserved.
		if ($reserved !== '') {
			$s = preg_replace_callback(
				'#%(' . substr(chunk_split(bin2hex($reserved), 2, '|'), 0, -1) . ')#i',
				fn(array $m): string => '%25' . strtoupper($m[1]),
				$s,
			) ?? throw new \LogicException('Regular expression failed in unescape()');
		}

		return rawurldecode($s);
	}


	/**
	 * Parses query string. Is affected by directive arg_separator.input.
	 * @return mixed[]
	 */
	public static function parseQuery(string $s): array
	{
		$s = str_replace(['%5B', '%5b'], '[', $s);
		$sep = preg_quote(ini_get('arg_separator.input') ?: '&');
		$s = preg_replace("#([$sep])([^[$sep=]+)([^$sep]*)#", '&0[$2]$3', '&' . $s) ?? throw new \LogicException('Regular expression failed in parseQuery()');
		parse_str($s, $res);
		return (array) ($res[0] ?? []);
	}


	/**
	 * Checks whether the URL is absolute, i.e. starts with a scheme followed by a colon.
	 */
	public static function isAbsolute(string $url): bool
	{
		return (bool) preg_match('#^[a-z][a-z0-9+.-]*:#i', $url);
	}


	/**
	 * Resolves dot segments (. and ..) in a URL path, as per RFC 3986.
	 */
	public static function removeDotSegments(string $path): string
	{
		$prefix = $segment = '';
		if (str_starts_with($path, '/')) {
			$prefix = '/';
			$path = substr($path, 1);
		}
		$segments = explode('/', $path);
		$res = [];
		foreach ($segments as $segment) {
			if ($segment === '..') {
				array_pop($res);
			} elseif ($segment !== '.') {
				$res[] = $segment;
			}
		}

		if ($segment === '.' || $segment === '..') {
			$res[] = '';
		}
		return $prefix . implode('/', $res);
	}
}
