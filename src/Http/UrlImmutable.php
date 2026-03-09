<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use function array_slice, explode, http_build_query, implode, ip2long, is_array, is_string, rawurlencode, str_starts_with, strrpos, substr;
use const PHP_QUERY_RFC3986;


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
 * @property-read array<string,mixed> $queryParameters
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

	/** @var mixed[] */
	private array $query = [];
	private string $fragment = '';
	private ?string $authority = null;


	/**
	 * @throws Nette\InvalidArgumentException if URL is malformed
	 */
	public function __construct(string|self|Url $url)
	{
		$url = is_string($url) ? new Url($url) : $url;
		[$this->scheme, $this->user, $this->password, $this->host, $this->port, $this->path, $this->query, $this->fragment] = $url->export();
	}


	public function withScheme(string $scheme): static
	{
		$dolly = clone $this;
		$dolly->scheme = $scheme;
		$dolly->authority = null;
		return $dolly;
	}


	public function getScheme(): string
	{
		return $this->scheme;
	}


	/** @deprecated */
	public function withUser(string $user): static
	{
		$dolly = clone $this;
		$dolly->user = $user;
		$dolly->authority = null;
		return $dolly;
	}


	/** @deprecated */
	public function getUser(): string
	{
		return $this->user;
	}


	/** @deprecated */
	public function withPassword(string $password): static
	{
		$dolly = clone $this;
		$dolly->password = $password;
		$dolly->authority = null;
		return $dolly;
	}


	/** @deprecated */
	public function getPassword(): string
	{
		return $this->password;
	}


	/** @deprecated */
	public function withoutUserInfo(): static
	{
		$dolly = clone $this;
		$dolly->user = $dolly->password = '';
		$dolly->authority = null;
		return $dolly;
	}


	public function withHost(string $host): static
	{
		$dolly = clone $this;
		$dolly->host = $host;
		$dolly->authority = null;
		return $dolly->setPath($dolly->path);
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


	public function withPort(int $port): static
	{
		$dolly = clone $this;
		$dolly->port = $port;
		$dolly->authority = null;
		return $dolly;
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
		return Url::$defaultPorts[$this->scheme] ?? null;
	}


	public function withPath(string $path): static
	{
		return (clone $this)->setPath($path);
	}


	private function setPath(string $path): static
	{
		$this->path = $this->host && !str_starts_with($path, '/') ? '/' . $path : $path;
		return $this;
	}


	public function getPath(): string
	{
		return $this->path;
	}


	/** @param string|mixed[] $query */
	public function withQuery(string|array $query): static
	{
		$dolly = clone $this;
		$dolly->query = is_array($query) ? $query : Url::parseQuery($query);
		return $dolly;
	}


	public function getQuery(): string
	{
		return http_build_query($this->query, '', '&', PHP_QUERY_RFC3986);
	}


	public function withQueryParameter(string $name, mixed $value): static
	{
		$dolly = clone $this;
		$dolly->query[$name] = $value;
		return $dolly;
	}


	/** @return mixed[] */
	public function getQueryParameters(): array
	{
		return $this->query;
	}


	/** @return mixed[]|string|null */
	public function getQueryParameter(string $name): array|string|null
	{
		return $this->query[$name] ?? null;
	}


	public function withFragment(string $fragment): static
	{
		$dolly = clone $this;
		$dolly->fragment = $fragment;
		return $dolly;
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
		return $this->authority ??= $this->host === ''
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
		return ($this->scheme === '' ? '' : $this->scheme . ':')
			. ($this->host === '' ? '' : '//' . $this->getAuthority());
	}


	public function __toString(): string
	{
		return $this->getAbsoluteUrl();
	}


	/**
	 * Checks whether two URLs are equal, ignoring query parameter order and trailing dots in hostnames.
	 */
	public function isEqual(string|Url|self $url): bool
	{
		return (new Url($this))->isEqual($url);
	}


	/**
	 * Resolves a URI reference against this URL the same way a browser would.
	 * Relative paths are resolved against the current path; paths starting with / are resolved against the host root.
	 */
	public function resolve(string $reference): self
	{
		$ref = new self($reference);
		if ($ref->scheme !== '') {
			$ref->path = Url::removeDotSegments($ref->path);
			return $ref;
		}

		$ref->scheme = $this->scheme;

		if ($ref->host !== '') {
			$ref->path = Url::removeDotSegments($ref->path);
			return $ref;
		}

		$ref->host = $this->host;
		$ref->port = $this->port;

		if ($ref->path === '') {
			$ref->path = $this->path;
			$ref->query = $ref->query ?: $this->query;
		} elseif (str_starts_with($ref->path, '/')) {
			$ref->path = Url::removeDotSegments($ref->path);
		} else {
			$ref->path = Url::removeDotSegments($this->mergePath($ref->path));
		}
		return $ref;
	}


	/** @internal */
	protected function mergePath(string $path): string
	{
		$pos = strrpos($this->path, '/');
		return $pos === false ? $path : substr($this->path, 0, $pos + 1) . $path;
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
}
