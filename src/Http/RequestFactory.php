<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;
use function in_array, is_array, is_string, sprintf, strlen;
use const PHP_SAPI;


/**
 * HTTP request factory.
 */
class RequestFactory
{
	/** @internal */
	private const ValidChars = '\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}';

	/**
	 * Regex-based filters applied to the URL before parsing. 'path' filters run on the path component only;
	 * 'url' filters run on the full request URI.
	 * @var array<string, array<string, string>>
	 */
	public array $urlFilters = [
		'path' => ['#//#' => '/'], // '%20' => ''
		'url' => [], // '#[.,)]$#D' => ''
	];

	private bool $binary = false;
	private bool $forceHttps = false;
	private ?Url $baseUrl = null;

	/** @var list<string> */
	private array $proxies = [];

	private bool $forwarded = true;
	private bool $xForwarded = true;


	/**
	 * Disables sanitization of request data (GET, POST, cookies, file names) for binary-safe handling.
	 */
	public function setBinary(bool $binary = true): static
	{
		$this->binary = $binary;
		return $this;
	}


	/**
	 * Sets the trusted proxy IP addresses or CIDR blocks used to resolve the real client IP and URL scheme,
	 * and which forwarding headers to trust from them ("Forwarded" and/or "X-Forwarded-*").
	 * @param string|list<string>  $proxy
	 */
	public function setProxy(string|array $proxy, bool $forwarded = true, bool $xForwarded = true): static
	{
		$this->proxies = (array) $proxy;
		$this->forwarded = $forwarded;
		$this->xForwarded = $xForwarded;
		return $this;
	}


	/**
	 * Forces the request scheme to HTTPS regardless of the server environment.
	 */
	public function setForceHttps(bool $forceHttps = true): static
	{
		$this->forceHttps = $forceHttps;
		return $this;
	}


	/**
	 * Sets the base URL of the application, used when it cannot be detected from the environment (CLI).
	 */
	public function setBaseUrl(string|Url $url): static
	{
		$this->baseUrl = new Url($url);
		if ($this->baseUrl->getHost() === '') {
			throw new Nette\InvalidArgumentException("Base URL '$url' must be absolute.");
		}

		$this->baseUrl->setPath(rtrim($this->baseUrl->getPath(), '/') . '/');
		return $this;
	}


	/**
	 * Returns new Request instance, using values from superglobals.
	 */
	public function fromGlobals(): Request
	{
		$url = new Url;
		$this->getServer($url);
		$this->getPathAndQuery($url);
		[$post, $cookies] = $this->getGetPostCookie($url);
		$remoteAddr = $this->getClient($url);
		if ($url->getHost() === '' && $this->baseUrl) {
			$url->setScheme($this->baseUrl->getScheme())
				->setHost($this->baseUrl->getHost())
				->setPath($scriptPath = $this->baseUrl->getPath());
			if (($port = $this->baseUrl->getPort()) && $port !== $this->baseUrl->getDefaultPort()) {
				$url->setPort($port);
			}
		} else {
			$scriptPath = $this->getScriptPath($url);
		}

		if ($this->forceHttps) {
			$url->setScheme('https');
		}

		return new Request(
			new UrlScript($url, $scriptPath),
			$post,
			$this->getFiles(),
			$cookies,
			$this->getHeaders(),
			$this->getMethod(),
			$remoteAddr,
			fn() => (string) file_get_contents('php://input'),
		);
	}


	private function getServer(Url $url): void
	{
		$url->setScheme(!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https' : 'http');

		if (
			(isset($_SERVER[$tmp = 'HTTP_HOST']) || isset($_SERVER[$tmp = 'SERVER_NAME']))
			&& ($pair = $this->parseHostAndPort($_SERVER[$tmp]))
		) {
			$url->setHost($pair[0]);
			if (isset($pair[1])) {
				$url->setPort($pair[1]);
			} elseif ($tmp === 'SERVER_NAME' && isset($_SERVER['SERVER_PORT'])) {
				$url->setPort((int) $_SERVER['SERVER_PORT']);
			}
		}
	}


	private function getPathAndQuery(Url $url): void
	{
		$requestUrl = $_SERVER['REQUEST_URI'] ?? '/';
		$requestUrl = preg_replace('#^\w++://[^/]++#', '', $requestUrl);
		$requestUrl = Strings::replace($requestUrl, $this->urlFilters['url']);

		$tmp = explode('?', $requestUrl, 2);
		$path = Url::unescape($tmp[0], '%/?#');
		$path = Strings::fixEncoding(Strings::replace($path, $this->urlFilters['path']));
		$url->setPath($path);
		$url->setQuery($tmp[1] ?? '');
	}


	private function getScriptPath(Url $url): string
	{
		if (PHP_SAPI === 'cli-server') {
			return '/';
		}

		$path = $url->getPath();
		$lpath = strtolower($path);
		$script = strtolower($_SERVER['SCRIPT_NAME'] ?? '');
		if ($lpath !== $script) {
			$max = min(strlen($lpath), strlen($script));
			for ($i = 0; $i < $max && $lpath[$i] === $script[$i]; $i++);
			$path = $i
				? substr($path, 0, strrpos($path, '/', $i - strlen($path) - 1) + 1)
				: '/';
		}

		return $path;
	}


	/** @return array{mixed[], mixed[]} */
	private function getGetPostCookie(Url $url): array
	{
		$useFilter = (!in_array((string) ini_get('filter.default'), ['', 'unsafe_raw'], strict: true) || ini_get('filter.default_flags'));

		$query = $url->getQueryParameters();
		$post = $useFilter
			? filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW)
			: (empty($_POST) ? [] : $_POST);
		$cookies = $useFilter
			? filter_input_array(INPUT_COOKIE, FILTER_UNSAFE_RAW)
			: (empty($_COOKIE) ? [] : $_COOKIE);

		// remove invalid characters
		$reChars = '#^[' . self::ValidChars . ']*+$#Du';
		if (!$this->binary) {
			$list = [&$query, &$post, &$cookies];
			foreach ($list as $key => &$val) {
				foreach ($val as $k => $v) {
					if (is_string($k) && (!preg_match($reChars, $k) || preg_last_error())) {
						unset($list[$key][$k]);

					} elseif (is_array($v)) {
						$list[$key][$k] = $v;
						$list[] = &$list[$key][$k];

					} elseif (is_string($v)) {
						$list[$key][$k] = (string) preg_replace('#[^' . self::ValidChars . ']+#u', '', $v);

					} else {
						throw new Nette\InvalidStateException(sprintf('Invalid value in $_POST/$_COOKIE in key %s, expected string, %s given.', "'$k'", get_debug_type($v)));
					}
				}
			}

			unset($list, $key, $val, $k, $v);
		}

		$url->setQuery($query);
		return [$post, $cookies];
	}


	/** @return mixed[] */
	private function getFiles(): array
	{
		$reChars = '#^[' . self::ValidChars . ']*+$#Du';
		$files = [];
		$list = [];
		foreach ($_FILES ?? [] as $k => $v) {
			if (
				!is_array($v)
				|| !isset($v['name'], $v['type'], $v['size'], $v['tmp_name'], $v['error'])
				|| (!$this->binary && is_string($k) && (!preg_match($reChars, $k) || preg_last_error()))
			) {
				continue;
			}

			$v['@'] = &$files[$k];
			$list[] = $v;
		}

		// create FileUpload objects
		foreach ($list as &$v) {
			if (!isset($v['name'])) {
				continue;

			} elseif (!is_array($v['name'])) {
				if (!$this->binary && (!preg_match($reChars, $v['name']) || preg_last_error())) {
					$v['name'] = '';
				}

				if ($v['error'] !== UPLOAD_ERR_NO_FILE) {
					$v['@'] = new FileUpload($v);
				}

				continue;
			}

			foreach ($v['name'] as $k => $foo) {
				if (!$this->binary && is_string($k) && (!preg_match($reChars, $k) || preg_last_error())) {
					continue;
				}

				$list[] = [
					'name' => $v['name'][$k],
					'type' => $v['type'][$k],
					'size' => $v['size'][$k],
					'full_path' => $v['full_path'][$k] ?? null,
					'tmp_name' => $v['tmp_name'][$k],
					'error' => $v['error'][$k],
					'@' => &$v['@'][$k],
				];
			}
		}

		return $files;
	}


	/** @return array<string, string> */
	private function getHeaders(): array
	{
		if (function_exists('apache_request_headers')) {
			$headers = apache_request_headers() ?: [];
		} else {
			$headers = [];
			foreach ($_SERVER as $k => $v) {
				if (str_starts_with($k, 'HTTP_')) {
					$k = substr($k, 5);
				} elseif (strncmp($k, 'CONTENT_', 8)) {
					continue;
				}

				$headers[strtr($k, '_', '-')] = $v;
			}
		}

		if (!isset($headers['Authorization'])) {
			if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				$headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
			} elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
				$headers['Authorization'] = 'Digest ' . $_SERVER['PHP_AUTH_DIGEST'];
			}
		}

		return $headers;
	}


	private function getMethod(): string
	{
		$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
		if (
			$method === 'POST'
			&& preg_match('#^[A-Z]+$#D', $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '')
		) {
			$method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
		}

		return $method;
	}


	private function getClient(Url $url): ?string
	{
		$remoteAddr = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

		// trust forwarding headers only when the request comes through a trusted proxy;
		// the proxy in turn should strip any forwarding header it does not set itself
		$client = $remoteAddr ? IPAddress::tryFrom($remoteAddr) : null;
		$usingTrustedProxy = $client && Arrays::some($this->proxies, fn(string $proxy): bool => $client->isInRange($proxy));
		if ($usingTrustedProxy) {
			return match (true) {
				$this->forwarded && !empty($_SERVER['HTTP_FORWARDED']) => $this->useForwardedProxy($url),
				$this->xForwarded => $this->useNonstandardProxy($url),
				default => $remoteAddr,
			};
		}

		return $remoteAddr;
	}


	private function useForwardedProxy(Url $url): ?string
	{
		// RFC 7239: split into hops (comma), each a set of params (semicolon)
		$hops = $addresses = [];
		foreach (explode(',', $_SERVER['HTTP_FORWARDED']) as $element) {
			$hop = [];
			foreach (explode(';', $element) as $pair) {
				[$key, $value] = explode('=', $pair, 2) + [1 => ''];
				$hop[strtolower(trim($key))] = trim($value, " \t\"");
			}

			$for = $hop['for'] ?? '';
			$addresses[] = str_contains($for, '[')
				? substr($for, 1, strpos($for, ']') - 1) // IPv6 "[addr]:port"
				: explode(':', $for)[0]; // IPv4 "addr:port" or bare address
			$hops[] = $hop;
		}

		$clientHop = $this->findClientHop($addresses);
		if ($clientHop === null) {
			return null;
		}

		// scheme and host from the client's own hop
		$hop = $hops[$clientHop[0]];
		if (isset($hop['proto'])) {
			$url->setScheme(strcasecmp($hop['proto'], 'https') === 0 ? 'https' : 'http');
			$url->setPort($url->getScheme() === 'https' ? 443 : 80);
		}

		if (isset($hop['host']) && ($pair = $this->parseHostAndPort($hop['host']))) {
			$url->setHost($pair[0]);
			if (isset($pair[1])) {
				$url->setPort($pair[1]);
			}
		}

		return $clientHop[1];
	}


	private function useNonstandardProxy(Url $url): ?string
	{
		if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
			$url->setScheme(strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0 ? 'https' : 'http');
			$url->setPort($url->getScheme() === 'https' ? 443 : 80);
		}

		if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
			$url->setPort((int) $_SERVER['HTTP_X_FORWARDED_PORT']);
		}

		if (empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			return null;
		}

		$clientHop = $this->findClientHop(array_map(trim(...), explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
		if ($clientHop === null) {
			return null;
		}

		if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			$hosts = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
			if (isset($hosts[$clientHop[0]]) && ($pair = $this->parseHostAndPort(trim($hosts[$clientHop[0]])))) {
				$url->setHost($pair[0]);
				if (isset($pair[1])) {
					$url->setPort($pair[1]);
				}
			}
		}

		return $clientHop[1];
	}


	/**
	 * Returns [index, address] of the rightmost hop after stripping trailing trusted proxies,
	 * or null when that hop is not a valid IP.
	 * @param  list<string>  $addresses
	 * @return array{int, string}|null
	 */
	private function findClientHop(array $addresses): ?array
	{
		$untrusted = array_filter(
			$addresses,
			fn(string $ip): bool => ($address = IPAddress::tryFrom($ip)) === null
				|| !Arrays::some($this->proxies, fn(string $proxy): bool => $address->isInRange($proxy)),
		);
		if (!$untrusted) {
			return null;
		}

		$index = array_key_last($untrusted);
		return IPAddress::tryFrom($untrusted[$index]) === null
			? null
			: [$index, $untrusted[$index]];
	}


	/** @return array{string, ?int}|null */
	private function parseHostAndPort(string $s): ?array
	{
		return preg_match('#^([a-z0-9_.-]+|\[[a-f0-9:]+])(:\d+)?$#Di', $s, $matches)
			? [
				rtrim(strtolower($matches[1]), '.'),
				isset($matches[2]) ? (int) substr($matches[2], 1) : null,
			]
			: null;
	}


	#[\Deprecated('use fromGlobals()')]
	public function createHttpRequest(): Request
	{
		trigger_error(__METHOD__ . '() is deprecated, use fromGlobals()', E_USER_DEPRECATED);
		return $this->fromGlobals();
	}
}
