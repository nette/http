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
	const CHARS = '#^[\x09\x0A\x0D\x20-\x7E\xA0-\x{10FFFF}]++\z#u';

	/** @var array */
	private $proxies = array();


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
		$url->setScheme(!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off') ? 'https' : 'http');
		$url->setUser(isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : '');
		$url->setPassword(isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '');

		// host & port
		if (isset($_SERVER[$tmp = 'HTTP_HOST']) || isset($_SERVER[$tmp = 'SERVER_NAME'])) {
			if (preg_match('#^([a-z0-9_.-]+|\[[a-f0-9:]+\])(:\d+)?\z#i', $_SERVER[$tmp], $pair)) {
				$url->setHost(strtolower($pair[1]));
				if (isset($pair[2])) {
					$url->setPort(substr($pair[2], 1));
				} elseif (isset($_SERVER['SERVER_PORT'])) {
					$url->setPort($_SERVER['SERVER_PORT']);
				}
			} else {
				throw new \Exception(); // TODO
			}
		}

		// path & query
		if (isset($_SERVER['REQUEST_URI'])) {
			$pos = strpos($_SERVER['REQUEST_URI'], '?');
			if ($pos !== FALSE) {
				$url->setPath(substr($_SERVER['REQUEST_URI'], 0, $pos));
				$url->setQuery(substr($_SERVER['REQUEST_URI'], $pos + 1));
			} else {
				$url->setPath($_SERVER['REQUEST_URI']);
			}
		}

		$url->canonicalize();
		$path = $url->getPath();
		if (!preg_match(self::CHARS, $path) || preg_last_error()) {
			throw new \Exception();
		}

		// detect script path
		$script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
		$max = min(strlen($path), strlen($script)) - 1;
		for ($i = 0, $j = 0; $i <= $max; $i++) {
			if ($path[$i] !== $script[$i] && strcasecmp($path[$i], $script[$i])) {
				break;
			} elseif ($path[$i] === '/') {
				$j = $i;
			} elseif ($i == $max) {
				$j = $i + 1;
			}
		}
		$url->setScriptPath(substr($path, 0, $j + 1));


		// GET, POST, COOKIE
		$useFilter = (!in_array(ini_get('filter.default'), array('', 'unsafe_raw')) || ini_get('filter.default_flags'));

		parse_str($url->getQuery(), $query);
		if (!$query) {
			$query = $useFilter ? filter_input_array(INPUT_GET, FILTER_UNSAFE_RAW) : (empty($_GET) ? array() : $_GET);
		}
		$post = $useFilter ? filter_input_array(INPUT_POST, FILTER_UNSAFE_RAW) : (empty($_POST) ? array() : $_POST);
		$cookies = $useFilter ? filter_input_array(INPUT_COOKIE, FILTER_UNSAFE_RAW) : (empty($_COOKIE) ? array() : $_COOKIE);

		$gpc = (bool) get_magic_quotes_gpc();

		// remove fucking quotes, control characters and check encoding
		$list = array(& $query, & $post, & $cookies);
		while (list($key, $val) = each($list)) {
			foreach ($val as $k => $v) {
				unset($list[$key][$k]);

				if ($gpc) {
					$k = stripslashes($k);
				}

				if (is_string($k) && (!preg_match(self::CHARS, $k) || preg_last_error())) {
					// invalid key -> ignore

				} elseif (is_array($v)) {
					$list[$key][$k] = $v;
					$list[] = & $list[$key][$k];

				} else {
					if ($gpc && !$useFilter) {
						$v = stripslashes($v);
					}
					if (!preg_match(self::CHARS, $v) || preg_last_error()) {
						$v = '';
					}
					$list[$key][$k] = $v;
				}
			}
		}
		unset($list, $key, $val, $k, $v);


		// FILES and create FileUpload objects
		$files = array();
		$list = array();
		if (!empty($_FILES)) {
			foreach ($_FILES as $k => $v) {
				if (is_string($k) && (!preg_match(self::CHARS, $k) || preg_last_error())) {
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
				if ($gpc) {
					$v['name'] = stripSlashes($v['name']);
				}
				if (!preg_match(self::CHARS, $v['name']) || preg_last_error()) {
					$v['name'] = '';
				}
				if ($v['error'] !== UPLOAD_ERR_NO_FILE) {
					$v['@'] = new FileUpload($v);
				}
				continue;
			}

			foreach ($v['name'] as $k => $foo) {
				if (is_string($k) && (!preg_match(self::CHARS, $k) || preg_last_error())) {
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


		if (isset($_SERVER['REQUEST_METHOD'])) {
			if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) && preg_match('#^[A-Z]+\z#', $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
				$method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
			} else {
				$method = $_SERVER['REQUEST_METHOD'];
			}
		} else {
			$method = NULL;
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

		return new Request($url, $query, $post, $files, $cookies, $headers, $method, $remoteAddr, $remoteHost, $rawBodyCallback);
	}

}
