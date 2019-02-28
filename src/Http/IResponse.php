<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;


/**
 * IHttpResponse interface.
 * @method self deleteHeader(string $name)
 */
interface IResponse
{
	/** @var int cookie expiration: forever (23.1.2037) */
	public const PERMANENT = 2116333333;

	/** @var int cookie expiration: until the browser is closed */
	public const BROWSER = 0;

	/** HTTP 1.1 response code */
	public const
		S100_CONTINUE = 100,
		S101_SWITCHING_PROTOCOLS = 101,
		S102_PROCESSING = 102,
		S200_OK = 200,
		S201_CREATED = 201,
		S202_ACCEPTED = 202,
		S203_NON_AUTHORITATIVE_INFORMATION = 203,
		S204_NO_CONTENT = 204,
		S205_RESET_CONTENT = 205,
		S206_PARTIAL_CONTENT = 206,
		S207_MULTI_STATUS = 207,
		S208_ALREADY_REPORTED = 208,
		S226_IM_USED = 226,
		S300_MULTIPLE_CHOICES = 300,
		S301_MOVED_PERMANENTLY = 301,
		S302_FOUND = 302,
		S303_SEE_OTHER = 303,
		S303_POST_GET = 303,
		S304_NOT_MODIFIED = 304,
		S305_USE_PROXY = 305,
		S307_TEMPORARY_REDIRECT = 307,
		S308_PERMANENT_REDIRECT = 308,
		S400_BAD_REQUEST = 400,
		S401_UNAUTHORIZED = 401,
		S402_PAYMENT_REQUIRED = 402,
		S403_FORBIDDEN = 403,
		S404_NOT_FOUND = 404,
		S405_METHOD_NOT_ALLOWED = 405,
		S406_NOT_ACCEPTABLE = 406,
		S407_PROXY_AUTHENTICATION_REQUIRED = 407,
		S408_REQUEST_TIMEOUT = 408,
		S409_CONFLICT = 409,
		S410_GONE = 410,
		S411_LENGTH_REQUIRED = 411,
		S412_PRECONDITION_FAILED = 412,
		S413_REQUEST_ENTITY_TOO_LARGE = 413,
		S414_REQUEST_URI_TOO_LONG = 414,
		S415_UNSUPPORTED_MEDIA_TYPE = 415,
		S416_REQUESTED_RANGE_NOT_SATISFIABLE = 416,
		S417_EXPECTATION_FAILED = 417,
		S421_MISDIRECTED_REQUEST = 421,
		S422_UNPROCESSABLE_ENTITY = 422,
		S423_LOCKED = 423,
		S424_FAILED_DEPENDENCY = 424,
		S426_UPGRADE_REQUIRED = 426,
		S428_PRECONDITION_REQUIRED = 428,
		S429_TOO_MANY_REQUESTS = 429,
		S431_REQUEST_HEADER_FIELDS_TOO_LARGE = 431,
		S451_UNAVAILABLE_FOR_LEGAL_REASONS = 451,
		S500_INTERNAL_SERVER_ERROR = 500,
		S501_NOT_IMPLEMENTED = 501,
		S502_BAD_GATEWAY = 502,
		S503_SERVICE_UNAVAILABLE = 503,
		S504_GATEWAY_TIMEOUT = 504,
		S505_HTTP_VERSION_NOT_SUPPORTED = 505,
		S506_VARIANT_ALSO_NEGOTIATES = 506,
		S507_INSUFFICIENT_STORAGE = 507,
		S508_LOOP_DETECTED = 508,
		S510_NOT_EXTENDED = 510,
		S511_NETWORK_AUTHENTICATION_REQUIRED = 511;

	/**
	 * Sets HTTP response code.
	 * @return static
	 */
	function setCode(int $code, string $reason = null);

	/**
	 * Returns HTTP response code.
	 */
	function getCode(): int;

	/**
	 * Sends a HTTP header and replaces a previous one.
	 * @return static
	 */
	function setHeader(string $name, string $value);

	/**
	 * Adds HTTP header.
	 * @return static
	 */
	function addHeader(string $name, string $value);

	/**
	 * Sends a Content-type HTTP header.
	 * @return static
	 */
	function setContentType(string $type, string $charset = null);

	/**
	 * Redirects to a new URL.
	 */
	function redirect(string $url, int $code = self::S302_FOUND): void;

	/**
	 * Sets the time (like '20 minutes') before a page cached on a browser expires, null means "must-revalidate".
	 * @return static
	 */
	function setExpiration(?string $expire);

	/**
	 * Checks if headers have been sent.
	 */
	function isSent(): bool;

	/**
	 * Returns value of an HTTP header.
	 */
	function getHeader(string $header): ?string;

	/**
	 * Returns a associative array of headers to sent.
	 */
	function getHeaders(): array;

	/**
	 * Sends a cookie.
	 * @param  string|int|\DateTimeInterface $expire  time, value 0 means "until the browser is closed"
	 * @return static
	 */
	function setCookie(string $name, string $value, $expire, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null);

	/**
	 * Deletes a cookie.
	 */
	function deleteCookie(string $name, string $path = null, string $domain = null, bool $secure = null);
}
