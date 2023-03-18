<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;


/**
 * HTTP request factory.
 */
class RequestFactory
{
	use Nette\SmartObject;

	/** @internal */
	private const ValidChars = '\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}';

	/** @var array */
	public $urlFilters = [
		'path' => ['#//#' => '/'], // '%20' => ''
		'url' => [], // '#[.,)]$#D' => ''
	];

	/** @var bool */
	private $binary = false;

	/** @var string[] */
	private $proxies = [];


	/** @return static */
	public function setBinary(bool $binary = true)
	{
		$this->binary = $binary;
		return $this;
	}


	/**
	 * @param  string|string[]  $proxy
	 * @return static
	 */
	public function setProxy($proxy)
	{
		$this->proxies = (array) $proxy;
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
		[$remoteAddr, $remoteHost] = $this->getClient($url);

		return new Request(
			new UrlScript($url, $this->getScriptPath($url)),
			$post,
			$this->getFiles(),
			$cookies,
			$this->getHeaders(),
			$this->getMethod(),
			$remoteAddr,
			$remoteHost,
			function (): string {
				return file_get_contents('php://input');
			}
		);
	}


	private function getServer(Url $url): void
	{
		$url->setScheme(!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https' : 'http');

		if (
			(isset($_SERVER[$tmp = 'HTTP_HOST']) || isset($_SERVER[$tmp = 'SERVER_NAME']))
			&& preg_match('#^([a-z0-9_.-]+|\[[a-f0-9:]+\])(:\d+)?$#Di', $_SERVER[$tmp], $pair)
		) {
			$url->setHost(rtrim(strtolower($pair[1]), '.'));
			if (isset($pair[2])) {
				$url->setPort((int) substr($pair[2], 1));
			} elseif (isset($_SERVER['SERVER_PORT'])) {
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


	private function getGetPostCookie(Url $url): array
	{
		$useFilter = (!in_array((string) ini_get('filter.default'), ['', 'unsafe_raw'], true) || ini_get('filter.default_flags'));

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
						throw new Nette\InvalidStateException(sprintf('Invalid value in $_POST/$_COOKIE in key %s, expected string, %s given.', "'$k'", gettype($v)));
					}
				}
			}

			unset($list, $key, $val, $k, $v);
		}

		$url->setQuery($query);
		return [$post, $cookies];
	}


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


	private function getHeaders(): array
	{
		if (function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
		} else {
			$headers = [];
			foreach ($_SERVER as $k => $v) {
				if (strncmp($k, 'HTTP_', 5) === 0) {
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


	private function getClient(Url $url): array
	{
		$remoteAddr = !empty($_SERVER['REMOTE_ADDR'])
			? trim($_SERVER['REMOTE_ADDR'], '[]') // workaround for PHP 7.3.0
			: null;

		// use real client address and host if trusted proxy is used
		$usingTrustedProxy = $remoteAddr && Arrays::some($this->proxies, function (string $proxy) use ($remoteAddr): bool {
			return Helpers::ipMatch($remoteAddr, $proxy);
		});
		if ($usingTrustedProxy) {
			$remoteHost = null;
			$remoteAddr = empty($_SERVER['HTTP_FORWARDED'])
				? $this->useNonstandardProxy($url)
				: $this->useForwardedProxy($url);

		} else {
			$remoteHost = !empty($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : null;
		}

		return [$remoteAddr, $remoteHost];
	}


	private function useForwardedProxy(Url $url): ?string
	{
		$forwardParams = preg_split('/[,;]/', $_SERVER['HTTP_FORWARDED']);
		foreach ($forwardParams as $forwardParam) {
			[$key, $value] = explode('=', $forwardParam, 2) + [1 => ''];
			$proxyParams[strtolower(trim($key))][] = trim($value, " \t\"");
		}

		if (isset($proxyParams['for'])) {
			$address = $proxyParams['for'][0];
			$remoteAddr = strpos($address, '[') === false
				? explode(':', $address)[0]  // IPv4
				: substr($address, 1, strpos($address, ']') - 1); // IPv6
		}

		if (isset($proxyParams['host']) && count($proxyParams['host']) === 1) {
			$host = $proxyParams['host'][0];
			$startingDelimiterPosition = strpos($host, '[');
			if ($startingDelimiterPosition === false) { //IPv4
				$pair = explode(':', $host);
				$url->setHost($pair[0]);
				if (isset($pair[1])) {
					$url->setPort((int) $pair[1]);
				}
			} else { //IPv6
				$endingDelimiterPosition = strpos($host, ']');
				$url->setHost(substr($host, strpos($host, '[') + 1, $endingDelimiterPosition - 1));
				$pair = explode(':', substr($host, $endingDelimiterPosition));
				if (isset($pair[1])) {
					$url->setPort((int) $pair[1]);
				}
			}
		}

		$scheme = (isset($proxyParams['proto']) && count($proxyParams['proto']) === 1)
			? $proxyParams['proto'][0]
			: 'http';
		$url->setScheme(strcasecmp($scheme, 'https') === 0 ? 'https' : 'http');
		return $remoteAddr ?? null;
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

		if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$xForwardedForWithoutProxies = array_filter(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']), function (string $ip): bool {
				return filter_var(trim($ip), FILTER_VALIDATE_IP) === false ||
					!Arrays::some($this->proxies, function (string $proxy) use ($ip): bool {
						return Helpers::ipMatch(trim($ip), $proxy);
					});
			});
			if ($xForwardedForWithoutProxies) {
				$remoteAddr = trim(end($xForwardedForWithoutProxies));
				$xForwardedForRealIpKey = key($xForwardedForWithoutProxies);
			}
		}

		if (isset($xForwardedForRealIpKey) && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
			$xForwardedHost = explode(',', $_SERVER['HTTP_X_FORWARDED_HOST']);
			if (isset($xForwardedHost[$xForwardedForRealIpKey])) {
				$url->setHost(trim($xForwardedHost[$xForwardedForRealIpKey]));
			}
		}

		return $remoteAddr ?? null;
	}


	/** @deprecated */
	public function createHttpRequest(): Request
	{
		return $this->fromGlobals();
	}
}
