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

	/** @var bool Whether the cookie is hidden from client-side */
	public $cookieHttpOnly = true;

	/** @var int HTTP response code */
	private $code = self::S200_OK;

	/** @var string */
	private $reason = self::REASON_PHRASES[self::S200_OK];

	/** @var string */
	private $version = '1.1';

	/** @var array of [name, values] */
	private $headers = [];

	/** @var string|\Closure */
	private $body = '';


	/**
	 * Sets HTTP protocol version.
	 * @return static
	 */
	public function setProtocolVersion(string $version)
	{
		$this->version = $version;
		return $this;
	}


	/**
	 * Returns HTTP protocol version.
	 */
	public function getProtocolVersion(): string
	{
		return $this->version;
	}


	/**
	 * Sets HTTP response code.
	 * @return static
	 * @throws Nette\InvalidArgumentException  if code is invalid
	 */
	public function setCode(int $code, string $reason = null)
	{
		if ($code < 100 || $code > 599) {
			throw new Nette\InvalidArgumentException("Bad HTTP response '$code'.");
		}
		$this->code = $code;
		$this->reason = $reason ?? self::REASON_PHRASES[$code] ?? 'Unknown status';
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
	 * Returns HTTP reason phrase.
	 */
	public function getReasonPhrase(): string
	{
		return $this->reason;
	}


	/**
	 * Sends a HTTP header and replaces a previous one.
	 * @return static
	 */
	public function setHeader(string $name, ?string $value)
	{
		unset($this->headers[strtolower($name)]);
		if ($value !== null) { // supports null for back compatibility
			$this->addHeader($name, $value);
		}
		return $this;
	}


	/**
	 * Adds HTTP header.
	 * @return static
	 */
	public function addHeader(string $name, string $value)
	{
		$lname = strtolower($name);
		$this->headers[$lname][0] = $name;
		$this->headers[$lname][1][] = trim(preg_replace('#[^\x20-\x7E\x80-\xFE]#', '', $value));
		return $this;
	}


	/**
	 * @return static
	 */
	public function deleteHeader(string $name)
	{
		unset($this->headers[strtolower($name)]);
		return $this;
	}


	/**
	 * Returns value of an HTTP header.
	 */
	public function getHeader(string $name): ?string
	{
		return $this->headers[strtolower($name)][1][0] ?? null;
	}


	/**
	 * Returns a associative array of headers to sent.
	 * @return string[][]
	 */
	public function getHeaders(): array
	{
		$res = [];
		foreach ($this->headers as $info) {
			$res[$info[0]] = $info[1];
		}
		return $res;
	}


	/**
	 * Sends a Content-type HTTP header.
	 * @return static
	 */
	public function setContentType(string $type, string $charset = null)
	{
		$this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
		return $this;
	}


	/**
	 * Redirects to a new URL. Note: call exit() after it.
	 */
	public function redirect(string $url, int $code = self::S302_FOUND): void
	{
		$this->setCode($code);
		$this->setHeader('Location', $url);
		if (preg_match('#^https?:|^\s*+[a-z0-9+.-]*+[^:]#i', $url)) {
			$escapedUrl = htmlspecialchars($url, ENT_IGNORE | ENT_QUOTES, 'UTF-8');
			$this->setBody("<h1>Redirect</h1>\n\n<p><a href=\"$escapedUrl\">Please click here to continue</a>.</p>");
		} else {
			$this->setBody('');
		}
	}


	/**
	 * Sets the time (like '20 minutes') before a page cached on a browser expires, null means "must-revalidate".
	 * @return static
	 */
	public function setExpiration(?string $time)
	{
		$this->setHeader('Pragma', null);
		if (!$time) { // no cache
			$this->setHeader('Cache-Control', 's-maxage=0, max-age=0, must-revalidate');
			$this->setHeader('Expires', 'Mon, 23 Jan 1978 10:00:00 GMT');
			return $this;
		}

		$time = DateTime::from($time);
		$this->setHeader('Cache-Control', 'max-age=' . ($time->format('U') - time()));
		$this->setHeader('Expires', Helpers::formatDate($time));
		return $this;
	}


	/**
	 * Sends a cookie.
	 * @param  string|int|\DateTimeInterface $time  expiration time, value 0 means "until the browser is closed"
	 * @return static
	 */
	public function setCookie(string $name, string $value, $expire, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, string $sameSite = null)
	{
		$path = $path === null ? $this->cookiePath : $path;
		$domain = $domain === null ? $this->cookieDomain : $domain;
		$secure = $secure === null ? $this->cookieSecure : $secure;
		$httpOnly = $httpOnly === null ? $this->cookieHttpOnly : $httpOnly;

		if (strpbrk($name . $path . $domain . $sameSite, "=,; \t\r\n\013\014") !== false) {
			throw new Nette\InvalidArgumentException('Cookie cannot contain any of the following \'=,; \t\r\n\013\014\'');
		}

		$value = $name . '=' . rawurlencode($value)
			. ($expire ? '; expires=' . Helpers::formatDate($expire) : '')
			. ($expire ? '; Max-Age=' . (DateTime::from($expire)->format('U') - time()) : '')
			. ($domain ? '; domain=' . $domain : '')
			. ($path ? '; path=' . $path : '')
			. ($secure ? '; secure' : '')
			. ($httpOnly ? '; HttpOnly' : '')
			. ($sameSite ? '; SameSite=' . $sameSite : '');

		$this->addHeader('Set-Cookie', $value);
		return $this;
	}


	/**
	 * Deletes a cookie.
	 */
	public function deleteCookie(string $name, string $path = null, string $domain = null, bool $secure = null): void
	{
		$this->setCookie($name, '', 0, $path, $domain, $secure);
	}


	/**
	 * @param  string|\Closure  $body
	 * @return static
	 */
	public function setBody($body)
	{
		if (!is_string($body) && !$body instanceof \Closure) {
			throw new Nette\InvalidArgumentException('Body must be string or Closure.');
		}
		$this->body = $body;
		return $this;
	}


	/**
	 * @return string|\Closure
	 */
	public function getBody()
	{
		return $this->body;
	}
}
