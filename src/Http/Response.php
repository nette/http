<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use function is_string;
use const ENT_IGNORE, ENT_QUOTES;


/**
 * Mutable HTTP response for setting status code, headers, cookies, and redirects.
 *
 * @property-read array<string, list<string>> $headers
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

	/** HTTP response code */
	private int $code = self::S200_OK;

	private string $reason = self::ReasonPhrases[self::S200_OK];

	private string $version = '1.1';

	/** @var array<string, array{string, list<string>}>  lowercased name => [name, values] */
	private array $headers = [];

	/** @var string|(\Closure(): void) */
	private string|\Closure $body = '';


	/**
	 * Sets HTTP protocol version.
	 */
	public function setProtocolVersion(string $version): static
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
	 * @throws Nette\InvalidArgumentException  if code is invalid
	 */
	public function setCode(int $code, ?string $reason = null): static
	{
		if ($code < 100 || $code > 599) {
			throw new Nette\InvalidArgumentException("Bad HTTP response '$code'.");
		}

		$this->code = $code;
		$this->reason = $reason ?? self::ReasonPhrases[$code] ?? 'Unknown status';
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
	 * Sets an HTTP header, replacing any previously set header with the same name.
	 */
	public function setHeader(string $name, ?string $value): static
	{
		unset($this->headers[strtolower($name)]);
		if ($value !== null) { // supports null for back compatibility
			$this->addHeader($name, $value);
		}

		return $this;
	}


	/**
	 * Adds an HTTP header without replacing a previously set header with the same name.
	 */
	public function addHeader(string $name, string $value): static
	{
		$lname = strtolower($name);
		$this->headers[$lname][0] = $name;
		$this->headers[$lname][1][] = trim((string) preg_replace('#[^\x20-\x7E\x80-\xFE]#', '', $value));
		return $this;
	}


	/**
	 * Deletes a previously set HTTP header.
	 */
	public function deleteHeader(string $name): static
	{
		unset($this->headers[strtolower($name)]);
		return $this;
	}


	/**
	 * Returns the HTTP header, or `null` if it does not exist. The parameter is case-insensitive.
	 */
	public function getHeader(string $header): ?string
	{
		return $this->headers[strtolower($header)][1][0] ?? null;
	}


	/**
	 * Returns all HTTP headers as associative array.
	 * @return array<string, list<string>>
	 */
	public function getHeaders(): array
	{
		$res = [];
		foreach ($this->headers as [$name, $values]) {
			$res[$name] = $values;
		}

		return $res;
	}


	/**
	 * Sets a Content-type HTTP header.
	 */
	public function setContentType(string $type, ?string $charset = null): static
	{
		$this->setHeader('Content-Type', $type . ($charset ? '; charset=' . $charset : ''));
		return $this;
	}


	/**
	 * Triggers a browser download dialog for the response body with the given filename.
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
	 */
	public function redirect(string $url, int $code = self::S302_Found): void
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
	 * Sets the Cache-Control and Expires headers. Pass a time string (e.g. '20 minutes') to enable caching,
	 * or null to disable it entirely.
	 */
	public function setExpiration(?string $expire): static
	{
		$seconds = Helpers::expirationToSeconds($expire);
		$this->setHeader('Pragma', null);
		if ($seconds === null || $seconds <= 0) { // no cache
			$this->setHeader('Cache-Control', 's-maxage=0, max-age=0, must-revalidate');
			$this->setHeader('Expires', 'Mon, 23 Jan 1978 10:00:00 GMT');
			return $this;
		}

		$this->setHeader('Cache-Control', 'max-age=' . $seconds);
		$this->setHeader('Expires', Helpers::formatDate(time() + $seconds));
		return $this;
	}


	/**
	 * Sets a cookie.
	 */
	public function setCookie(
		string $name,
		string $value,
		string|int|\DateTimeInterface|null $expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		bool $httpOnly = true,
		SameSite|string|null $sameSite = SameSite::Lax,
		bool $partitioned = false,
	): static
	{
		$sameSite = $sameSite instanceof SameSite ? $sameSite->value : $sameSite;
		[$path, $domain] = [
			$path ?? ($domain === null ? $this->cookiePath : '/'),
			$domain ?? ($path === null ? $this->cookieDomain : ''),
		];
		if ($name === '' || preg_match('#[=,; \t\r\n\x0B\x0C]#', $name)) {
			throw new Nette\InvalidArgumentException("Cookie name must not be empty or contain '=', ',', ';', whitespace or control characters, '$name' given.");
		} elseif (preg_match('#[,; \t\r\n\x0B\x0C]#', $path . $domain . $sameSite)) {
			throw new Nette\InvalidArgumentException("Cookie path, domain and sameSite must not contain ',', ';', whitespace or control characters.");
		} elseif ($expire === 0) { // BC: 0 used to mean a session cookie
			trigger_error('Passing 0 as $expire is deprecated; use null for a session cookie.', E_USER_DEPRECATED);
			$expire = null;
		}

		$seconds = Helpers::expirationToSeconds($expire);
		// both SameSite=None and Partitioned are rejected by the browser without the Secure attribute
		$secure = $sameSite === SameSite::None->value || $partitioned || ($secure ?? $this->cookieSecure);
		$cookie = $name . '=' . rawurlencode($value)
			. ($seconds === null ? '' : '; expires=' . Helpers::formatDate(time() + $seconds) . '; Max-Age=' . max(0, $seconds))
			. '; path=' . $path
			. ($domain === '' ? '' : '; domain=' . $domain)
			. ($secure ? '; secure' : '')
			. ($httpOnly ? '; HttpOnly' : '')
			. '; SameSite=' . $sameSite
			. ($partitioned ? '; Partitioned' : '');
		$this->addHeader('Set-Cookie', $cookie);
		return $this;
	}


	/**
	 * Deletes a cookie.
	 */
	public function deleteCookie(
		string $name,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
	): void
	{
		$this->setCookie($name, '', -1, $path, $domain, $secure);
	}


	/** @param  string|(\Closure(): void)  $body */
	public function setBody(string|\Closure $body): static
	{
		$this->body = $body;
		return $this;
	}


	/** @return string|(\Closure(): void) */
	public function getBody(): string|\Closure
	{
		return $this->body;
	}


	public function __toString(): string
	{
		$res = "HTTP/$this->version $this->code $this->reason\r\n";
		foreach ($this->headers as [$name, $values]) {
			foreach ($values as $value) {
				$res .= $name . ': ' . $value . "\r\n";
			}
		}

		if (is_string($this->body)) {
			$res .= "\r\n" . $this->body;
		} else {
			ob_start(function (): void {});
			try {
				($this->body)();
				$res .= "\r\n" . ob_get_clean();
			} catch (\Throwable $e) {
				ob_end_clean();
				throw $e;
			}
		}

		return $res;
	}
}
