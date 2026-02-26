<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use Nette\Utils\DateTime;
use function array_map, explode, implode, inet_pton, sprintf, strlen, strncmp, unpack;


/**
 * Rendering helpers for HTTP.
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
	 */
	public static function ipMatch(string $ip, string $mask): bool
	{
		[$mask, $size] = explode('/', $mask . '/');
		$ipBin = inet_pton($ip);
		$maskBin = inet_pton($mask);
		if ($ipBin === false || $maskBin === false) {
			return false;
		}

		$tmp = fn(int $n): string => sprintf('%032b', $n);
		$ip = implode('', array_map($tmp, unpack('N*', $ipBin) ?: []));
		$mask = implode('', array_map($tmp, unpack('N*', $maskBin) ?: []));
		$max = strlen($ip);
		if (!$max || $max !== strlen($mask) || (int) $size < 0 || (int) $size > $max) {
			return false;
		}

		return strncmp($ip, $mask, $size === '' ? $max : (int) $size) === 0;
	}


	/**
	 * Sends the strict same-site cookie used to detect same-site requests.
	 */
	public static function initCookie(IRequest $request, IResponse $response): void
	{
		$response->setCookie(self::StrictCookieName, '1', 0, '/', sameSite: IResponse::SameSiteStrict);
	}
}
