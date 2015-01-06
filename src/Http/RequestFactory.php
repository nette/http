<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Http;

use Nette,
	Nette\Utils\Strings;


/**
 * Current HTTP request factory.
 *
 * @author     David Grudl
 */
class RequestFactory extends Nette\Object
{
	/** @internal */
	const CHARS = '#^[\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]*+\z#u';

	/** @var array */
	public $urlFilters = array(
		'path' => array('#/{2,}#' => '/'), // '%20' => ''
		'url' => array(), // '#[.,)]\z#' => ''
	);

	/** @var bool */
	private $binary = FALSE;

	/** @var array */
	private $proxies = array();


	/**
	 * @param  bool
	 * @return self
	 */
	public function setBinary($binary = TRUE)
	{
		$this->binary = (bool) $binary;
		return $this;
	}


	/**
	 * @param  array|string
	 * @return self
	 */
	public function setProxy($proxy)
	{
		$this->proxies = (array) $proxy;
		return $this;
	}


	/**
	 * Creates current HttpRequest object.
	 * @return Request
	 */
	public function createHttpRequest()
	{
		// DETECTS URI, base path and script path of the request.
		$url = new UrlScript;
		$url = $url->setScheme(!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https' : 'http');
		$url = $url->setUser(isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '');
		$url = $url->setPassword(isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '');

		// host & port
		if ((isset($_SERVER[$tmp = 'HTTP_HOST']) || isset($_SERVER[$tmp = 'SERVER_NAME']))
			&& preg_match('#^([a-z0-9_.-]+|\[[a-f0-9:]+\])(:\d+)?\z#i', $_SERVER[$tmp], $pair)
		) {
			$url = $url->setHost(strtolower($pair[1]));
			if (isset($pair[2])) {
				$url = $url->setPort(substr($pair[2], 1));
			} elseif (isset($_SERVER['SERVER_PORT'])) {
				$url = $url->setPort($_SERVER['SERVER_PORT']);
			}
		}

		// path & query
		$requestUrl = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		$requestUrl = Strings::replace($requestUrl, $this->urlFilters['url']);
		$tmp = explode('?', $requestUrl, 2);
		$path = Url::unescape($tmp[0], '%/?#');
		$path = Strings::fixEncoding(Strings::replace($path, $this->urlFilters['path']));
		$url = $url->setPath($path);
		$url = $url->setQuery(isset($tmp[1]) ? $tmp[1] : '');

		// detect script path
		$script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
		if ($path !== $script) {
			$max = min(strlen($path), strlen($script));
			for ($i = 0; $i < $max && $path[$i] === $script[$i]; $i++);
			$path = $i ? substr($path, 0, strrpos($path, '/', $i - strlen($path) - 1) + 1) : '/';
		}
		$url = $url->setScriptPath($path);

		// GET, POST, COOKIE
		$useFilter = (!in_array(ini_get('filter.default'), array('', 'unsafe_raw')) || ini_get('filter.default_flags'));

		$query = $url->getQueryParameters();
		$post = $useFilter ? filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) : (empty($_POST) ? array() : $_POST);
		$cookies = $useFilter ? filter_input_array(INPUT_COOKIE, FILTER_UNSAFE_RAW) : (empty($_COOKIE) ? array() : $_COOKIE);

		if (get_magic_quotes_gpc()) {
			$post = Helpers::stripslashes($post, $useFilter);
			$cookies = Helpers::stripslashes($cookies, $useFilter);
		}

		// remove invalid characters
		if (!$this->binary) {
			$list = array(& $query, & $post, & $cookies);
			while (list($key, $val) = each($list)) {
				foreach ($val as $k => $v) {
					if (is_string($k) && (!preg_match(self::CHARS, $k) || preg_last_error())) {
						unset($list[$key][$k]);

					} elseif (is_array($v)) {
						$list[$key][$k] = $v;
						$list[] = & $list[$key][$k];

					} elseif (!preg_match(self::CHARS, $v) || preg_last_error()) {
						$list[$key][$k] = '';
					}
				}
			}
			unset($list, $key, $val, $k, $v);
		}
		$url = $url->setQuery($query);


		// FILES and create FileUpload objects
		$files = array();
		$list = array();
		if (!empty($_FILES)) {
			foreach ($_FILES as $k => $v) {
				if (!$this->binary && is_string($k) && (!preg_match(self::CHARS, $k) || preg_last_error())) {
					continue;
				}
				$v['@'] = & $files[$k];
				$list[] = $v;
			}
		}

		while (list(, $v) = each($list)) {
			if (!isset($v['name'])) {
				continue;

			} elseif (!is_array($v['name'])) {
				if (get_magic_quotes_gpc()) {
					$v['name'] = stripSlashes($v['name']);
				}
				if (!$this->binary && (!preg_match(self::CHARS, $v['name']) || preg_last_error())) {
					$v['name'] = '';
				}
				if ($v['error'] !== UPLOAD_ERR_NO_FILE) {
					$v['@'] = new FileUpload($v);
				}
				continue;
			}

			foreach ($v['name'] as $k => $foo) {
				if (!$this->binary && is_string($k) && (!preg_match(self::CHARS, $k) || preg_last_error())) {
					continue;
				}
				$list[] = array(
					'name' => $v['name'][$k],
					'type' => $v['type'][$k],
					'size' => $v['size'][$k],
					'tmp_name' => $v['tmp_name'][$k],
					'error' => $v['error'][$k],
					'@' => & $v['@'][$k],
				);
			}
		}


		// HEADERS
		if (function_exists('apache_request_headers')) {
			$headers = apache_request_headers();
		} else {
			$headers = array();
			foreach ($_SERVER as $k => $v) {
				if (strncmp($k, 'HTTP_', 5) == 0) {
					$k = substr($k, 5);
				} elseif (strncmp($k, 'CONTENT_', 8)) {
					continue;
				}
				$headers[ strtr($k, '_', '-') ] = $v;
			}
		}


		$remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : NULL;
		$remoteHost = isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : NULL;

		// proxy
		foreach ($this->proxies as $proxy) {
			if (Helpers::ipMatch($remoteAddr, $proxy)) {
				if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
					$remoteAddr = trim(current(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])));
				}
				if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
					$remoteHost = trim(current(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])));
				}
				break;
			}
		}


		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : NULL;
		if ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])
			&& preg_match('#^[A-Z]+\z#', $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])
		) {
			$method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
		}

		// raw body
		$rawBodyCallback = function() {
			static $rawBody;

			if (PHP_VERSION_ID >= 50600) {
				return file_get_contents('php://input');

			} elseif ($rawBody === NULL) { // can be read only once in PHP < 5.6
				$rawBody = (string) file_get_contents('php://input');
			}

			return $rawBody;
		};

		return new Request($url, NULL, $post, $files, $cookies, $headers, $method, $remoteAddr, $remoteHost, $rawBodyCallback);
	}

}
