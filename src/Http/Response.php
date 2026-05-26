<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use Nette\Utils\DateTime;
use function array_filter, header, header_remove, headers_list, headers_sent, htmlspecialchars, http_response_code, ini_get, is_int, ltrim, ob_get_length, ob_get_status, preg_match, rawurlencode, setcookie, str_replace, strcasecmp, strlen, strncasecmp, substr, time;
use const PHP_SAPI;


/**
 * Mutable HTTP response for setting status code, headers, cookies, and redirects.
 *
 * @property-read array<string,string> $headers
 */
final class Response implements IResponse
{
	use Nette\SmartObject;

	/** The domain in which the cookie will be available */
	public string $cookieDomain = '';

	/** The path in which the cookie will be available */
	public string $cookiePath = '/';

	/** Whether the cookie is available only through HTTPS */
	public bool $cookieSecure = false;

	/** Whether to warn when there is data in the output buffer before sending headers */
	public bool $warnOnBuffer = true;

	/** HTTP response code */
	private int $code = self::S200_OK;


	public function __construct()
	{
		if (is_int($code = http_response_code())) {
			$this->code = $code;
		}
	}


	/**
	 * Sets HTTP response code.
	 * @throws Nette\InvalidArgumentException  if code is invalid
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setCode(int $code, ?string $reason = null): static
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
	 * Sends an HTTP header, replacing any previously sent header with the same name.
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setHeader(string $name, ?string $value): static
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
	 * Adds an HTTP header without replacing a previously sent header with the same name.
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function addHeader(string $name, string $value): static
	{
		self::checkHeaders();
		header($name . ': ' . $value, replace: false);
		return $this;
	}


	/**
	 * Deletes a previously sent HTTP header.
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function deleteHeader(string $name): static
	{
		self::checkHeaders();
		header_remove($name);
		return $this;
	}


	/**
	 * Sends a Content-type HTTP header.
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setContentType(string $type, ?string $charset = null): static
	{
		$this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
		return $this;
	}


	/**
	 * Triggers a browser download dialog for the response body with the given filename.
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function sendAsFile(string $fileName): static
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
	 * Sets the Cache-Control and Expires headers. Pass a time string (e.g. '20 minutes') to enable caching,
	 * or null to disable it entirely.
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setExpiration(?string $expire): static
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
	 * Checks whether HTTP headers have already been sent, making it impossible to modify them.
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
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		$headers = [];
		foreach (headers_list() as $header) {
			$parts = explode(':', $header, 2);
			if (isset($parts[1])) {
				$headers[$parts[0]] = ltrim($parts[1]);
			}
		}

		return $headers;
	}


	/**
	 * Sends a cookie.
	 * @param self::SameSite*|null  $sameSite
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function setCookie(
		string $name,
		string $value,
		string|int|\DateTimeInterface|null $expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		?bool $httpOnly = null,
		?string $sameSite = null,
	): static
	{
		self::checkHeaders();
		setcookie($name, $value, [
			'expires' => $expire ? (int) DateTime::from($expire)->format('U') : 0,
			'path' => $path ?? ($domain ? '/' : $this->cookiePath),
			'domain' => $domain ?? ($path ? '' : $this->cookieDomain),
			'secure' => $secure ?? $this->cookieSecure,
			'httponly' => $httpOnly ?? true,
			'samesite' => $sameSite ?? self::SameSiteLax,
		]);
		return $this;
	}


	/**
	 * Deletes a cookie.
	 * @throws Nette\InvalidStateException  if HTTP headers have been sent
	 */
	public function deleteCookie(
		string $name,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
	): void
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
			!array_filter(ob_get_status(full_status: true), fn(array $i): bool => !$i['chunk_size'])
		) {
			trigger_error('Possible problem: you are sending a HTTP header while already having some data in output buffer. Try Tracy\OutputDebugger or send cookies/start session earlier.');
		}
	}
}
