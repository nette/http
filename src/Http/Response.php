<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;
use Nette\Utils\DateTime;


/**
 * HttpResponse class.
 *
 * @property-read array $headers
 */
final class Response implements IResponse
{
	use Nette\SmartObject;

	/** @var string The domain in which the cookie will be available */
	public $cookieDomain = '';

	/** @var string The path in which the cookie will be available */
	public $cookiePath = '/';

	/** @var bool Whether the cookie is available only through HTTPS */
	public $cookieSecure = false;

	/** @deprecated */
	public $cookieHttpOnly;

	/** @var bool Whether warn on possible problem with data in output buffer */
	public $warnOnBuffer = true;

	/** @deprecated */
	private static $fixIE = true;

	/** @var int HTTP response code */
	private $code = self::S200_OK;


	public function __construct()
	{
		if (is_int($code = http_response_code())) {
			$this->code = $code;
		}
	}


	/**
	 * Sets HTTP response code.
	 * @return static
	 * @throws Nette\InvalidArgumentException  if code is invalid
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setCode(int $code, ?string $reason = null)
	{
		if ($code < 100 || $code > 599) {
			throw new Nette\InvalidArgumentException("Bad HTTP response '$code'.");
		}

		self::checkHeaders();
		$this->code = $code;
		$protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
		$reason ??= self::ReasonPhrases[$code] ?? 'Unknown status';
		header("$protocol $code $reason");
		return $this;
	}


	/**
	 * Returns HTTP response code.
	 */
	public function getCode(): int
	{
		return $this->code;
	}


	/**
	 * Sends an HTTP header and overwrites previously sent header of the same name.
	 * @return static
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setHeader(string $name, ?string $value)
	{
		self::checkHeaders();
		if ($value === null) {
			header_remove($name);
		} elseif (strcasecmp($name, 'Content-Length') === 0 && ini_get('zlib.output_compression')) {
			// ignore, PHP bug #44164
		} else {
			header($name . ': ' . $value);
		}

		return $this;
	}


	/**
	 * Sends an HTTP header and doesn't overwrite previously sent header of the same name.
	 * @return static
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function addHeader(string $name, string $value)
	{
		self::checkHeaders();
		header($name . ': ' . $value, replace: false);
		return $this;
	}


	/**
	 * Deletes a previously sent HTTP header.
	 * @return static
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function deleteHeader(string $name)
	{
		self::checkHeaders();
		header_remove($name);
		return $this;
	}


	/**
	 * Sends a Content-type HTTP header.
	 * @return static
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setContentType(string $type, ?string $charset = null)
	{
		$this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
		return $this;
	}


	/**
	 * Response should be downloaded with 'Save as' dialog.
	 * @return static
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function sendAsFile(string $fileName)
	{
		$this->setHeader(
			'Content-Disposition',
			'attachment; filename="' . str_replace('"', '', $fileName) . '"; '
			. "filename*=utf-8''" . rawurlencode($fileName),
		);
		return $this;
	}


	/**
	 * Redirects to another URL. Don't forget to quit the script then.
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function redirect(string $url, int $code = self::S302_Found): void
	{
		$this->setCode($code);
		$this->setHeader('Location', $url);
		if (preg_match('#^https?:|^\s*+[a-z0-9+.-]*+[^:]#i', $url)) {
			$escapedUrl = htmlspecialchars($url, ENT_IGNORE | ENT_QUOTES, 'UTF-8');
			echo "<h1>Redirect</h1>\n\n<p><a href=\"$escapedUrl\">Please click here to continue</a>.</p>";
		}
	}


	/**
	 * Sets the expiration of the HTTP document using the `Cache-Control` and `Expires` headers.
	 * The parameter is either a time interval (as text) or `null`, which disables caching.
	 * @return static
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setExpiration(?string $expire)
	{
		$this->setHeader('Pragma', null);
		if (!$expire) { // no cache
			$this->setHeader('Cache-Control', 's-maxage=0, max-age=0, must-revalidate');
			$this->setHeader('Expires', 'Mon, 23 Jan 1978 10:00:00 GMT');
			return $this;
		}

		$expire = DateTime::from($expire);
		$this->setHeader('Cache-Control', 'max-age=' . ($expire->format('U') - time()));
		$this->setHeader('Expires', Helpers::formatDate($expire));
		return $this;
	}


	/**
	 * Returns whether headers have already been sent from the server to the browser,
	 * so it is no longer possible to send headers or change the response code.
	 */
	public function isSent(): bool
	{
		return headers_sent();
	}


	/**
	 * Returns the sent HTTP header, or `null` if it does not exist. The parameter is case-insensitive.
	 */
	public function getHeader(string $header): ?string
	{
		if (func_num_args() > 1) {
			trigger_error(__METHOD__ . '() parameter $default is deprecated, use operator ??', E_USER_DEPRECATED);
		}

		$header .= ':';
		$len = strlen($header);
		foreach (headers_list() as $item) {
			if (strncasecmp($item, $header, $len) === 0) {
				return ltrim(substr($item, $len));
			}
		}

		return null;
	}


	/**
	 * Returns all sent HTTP headers as associative array.
	 */
	public function getHeaders(): array
	{
		$headers = [];
		foreach (headers_list() as $header) {
			$a = strpos($header, ':');
			$headers[substr($header, 0, $a)] = (string) substr($header, $a + 2);
		}

		return $headers;
	}


	/**
	 * Sends a cookie.
	 * @param  string|int|\DateTimeInterface $expire  expiration time, value null means "until the browser session ends"
	 * @return static
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setCookie(
		string $name,
		string $value,
		$expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		?bool $httpOnly = null,
		?string $sameSite = null,
	) {
		self::checkHeaders();
		$options = [
			'expires' => $expire ? (int) DateTime::from($expire)->format('U') : 0,
			'path' => $path ?? ($domain ? '/' : $this->cookiePath),
			'domain' => $domain ?? ($path ? '' : $this->cookieDomain),
			'secure' => $secure ?? $this->cookieSecure,
			'httponly' => $httpOnly ?? true,
			'samesite' => $sameSite = ($sameSite ?? self::SameSiteLax),
		];
		if (PHP_VERSION_ID >= 70300) {
			setcookie($name, $value, $options);
		} else {
			setcookie(
				$name,
				$value,
				$options['expires'],
				$options['path'] . ($sameSite ? "; SameSite=$sameSite" : ''),
				$options['domain'],
				$options['secure'],
				$options['httponly'],
			);
		}

		return $this;
	}


	/**
	 * Deletes a cookie.
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function deleteCookie(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null): void
	{
		$this->setCookie($name, '', 0, $path, $domain, $secure);
	}


	private function checkHeaders(): void
	{
		if (PHP_SAPI === 'cli') {
		} elseif (headers_sent($file, $line)) {
			throw new Nette\InvalidStateException('Cannot send header after HTTP headers have been sent' . ($file ? " (output started at $file:$line)." : '.'));

		} elseif (
			$this->warnOnBuffer &&
			ob_get_length() &&
			!array_filter(ob_get_status(true), fn(array $i): bool => !$i['chunk_size'])
		) {
			trigger_error('Possible problem: you are sending a HTTP header while already having some data in output buffer. Try Tracy\OutputDebugger or send cookies/start session earlier.');
		}
	}
}
