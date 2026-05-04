<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use Nette\Utils\DateTime;


/**
 * Helper functions for HTTP requests, responses and headers.
 */
final class Helpers
{
	use Nette\StaticClass;

	/** @internal */
	public const StrictCookieName = '_nss';

	/** @deprecated */
	public const STRICT_COOKIE_NAME = self::StrictCookieName;


	/**
	 * Formats a date and time in the HTTP date format (RFC 7231), e.g. 'Mon, 23 Jan 1978 10:00:00 GMT'.
	 */
	public static function formatDate(string|int|\DateTimeInterface $time): string
	{
		$time = DateTime::from($time)->setTimezone(new \DateTimeZone('GMT'));
		return $time->format('D, d M Y H:i:s \G\M\T');
	}


	/**
	 * Checks whether an IP address falls within a CIDR block (e.g. '192.168.1.0/24').
	 * @deprecated use IPAddress class
	 */
	public static function ipMatch(string $ip, string $mask): bool
	{
		return IPAddress::tryFrom($ip)?->isInRange($mask) ?? false;
	}


	/**
	 * Sends the strict same-site cookie used to detect same-site requests.
	 */
	public static function initCookie(IRequest $request, IResponse $response): void
	{
		$response->setCookie(self::StrictCookieName, '1', 0, '/', sameSite: IResponse::SameSiteStrict);
	}
}
