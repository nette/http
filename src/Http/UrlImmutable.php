<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


/**
 * Immutable representation of a URL.
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
 * @property-read string $scheme
 * @property-read string $user
 * @property-read string $password
 * @property-read string $host
 * @property-read int $port
 * @property-read string $path
 * @property-read string $query
 * @property-read string $fragment
 * @property-read string $absoluteUrl
 * @property-read string $authority
 * @property-read string $hostUrl
 * @property-read array $queryParameters
 */
class UrlImmutable implements \JsonSerializable
{
	use Nette\SmartObject;

	private string $scheme = '';
	private string $user = '';
	private string $password = '';
	private string $host = '';
	private ?int $port = null;
	private string $path = '';
	private array $query = [];
	private string $fragment = '';
	private string $authority = '';


	/**
	 * @param  string|self|Url  $url
	 * @throws Nette\InvalidArgumentException if URL is malformed
	 */
	public function __construct($url)
	{
		if (!$url instanceof Url && !$url instanceof self && !is_string($url)) {
			throw new Nette\InvalidArgumentException;
		}

		$url = is_string($url) ? new Url($url) : $url;
		[$this->scheme, $this->user, $this->password, $this->host, $this->port, $this->path, $this->query, $this->fragment] = $url->export();
		$this->build();
	}


	/** @return static */
	public function withScheme(string $scheme)
	{
		$dolly = clone $this;
		$dolly->scheme = $scheme;
		$dolly->build();
		return $dolly;
	}


	public function getScheme(): string
	{
		return $this->scheme;
	}


	/** @return static */
	public function withUser(string $user)
	{
		$dolly = clone $this;
		$dolly->user = $user;
		$dolly->build();
		return $dolly;
	}


	public function getUser(): string
	{
		return $this->user;
	}


	/** @return static */
	public function withPassword(string $password)
	{
		$dolly = clone $this;
		$dolly->password = $password;
		$dolly->build();
		return $dolly;
	}


	public function getPassword(): string
	{
		return $this->password;
	}


	/** @return static */
	public function withoutUserInfo()
	{
		$dolly = clone $this;
		$dolly->user = $dolly->password = '';
		$dolly->build();
		return $dolly;
	}


	/** @return static */
	public function withHost(string $host)
	{
		$dolly = clone $this;
		$dolly->host = $host;
		$dolly->build();
		return $dolly;
	}


	public function getHost(): string
	{
		return $this->host;
	}


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
	public function withPort(int $port)
	{
		$dolly = clone $this;
		$dolly->port = $port;
		$dolly->build();
		return $dolly;
	}


	public function getPort(): ?int
	{
		return $this->port ?: $this->getDefaultPort();
	}


	public function getDefaultPort(): ?int
	{
		return Url::$defaultPorts[$this->scheme] ?? null;
	}


	/** @return static */
	public function withPath(string $path)
	{
		$dolly = clone $this;
		$dolly->path = $path;
		$dolly->build();
		return $dolly;
	}


	public function getPath(): string
	{
		return $this->path;
	}


	/**
	 * @param  string|array  $query
	 * @return static
	 */
	public function withQuery($query)
	{
		$dolly = clone $this;
		$dolly->query = is_array($query) ? $query : Url::parseQuery($query);
		$dolly->build();
		return $dolly;
	}


	public function getQuery(): string
	{
		return http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
	}


	/**
	 * @param mixed  $value  null unsets the parameter
	 * @return static
	 */
	public function withQueryParameter(string $name, $value)
	{
		$dolly = clone $this;
		$dolly->query[$name] = $value;
		return $dolly;
	}


	public function getQueryParameters(): array
	{
		return $this->query;
	}


	/** @return array|string|null */
	public function getQueryParameter(string $name)
	{
		return $this->query[$name] ?? null;
	}


	/** @return static */
	public function withFragment(string $fragment)
	{
		$dolly = clone $this;
		$dolly->fragment = $fragment;
		$dolly->build();
		return $dolly;
	}


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
		return $this->authority;
	}


	/**
	 * Returns the scheme and authority part of URI.
	 */
	public function getHostUrl(): string
	{
		return ($this->scheme ? $this->scheme . ':' : '')
			. ($this->authority !== '' ? '//' . $this->authority : '');
	}


	public function __toString(): string
	{
		return $this->getAbsoluteUrl();
	}


	/**
	 * @param  string|Url|self  $url
	 */
	public function isEqual($url): bool
	{
		return (new Url($this))->isEqual($url);
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


	protected function build(): void
	{
		if ($this->host && substr($this->path, 0, 1) !== '/') {
			$this->path = '/' . $this->path;
		}

		$this->authority = $this->host === ''
			? ''
			: ($this->user !== ''
				? rawurlencode($this->user) . ($this->password === '' ? '' : ':' . rawurlencode($this->password)) . '@'
				: '')
			. $this->host
			. ($this->port && $this->port !== $this->getDefaultPort()
				? ':' . $this->port
				: '');
	}
}
