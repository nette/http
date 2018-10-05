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
 * Rendering helpers for HTTP.
 */
final class Helpers
{
	use Nette\StaticClass;

	/**
	 * Returns HTTP valid date format.
	 * @param  string|int|\DateTimeInterface  $time
	 */
	public static function formatDate($time): string
	{
		$time = DateTime::from($time)->setTimezone(new \DateTimeZone('GMT'));
		return $time->format('D, d M Y H:i:s \G\M\T');
	}


	/**
	 * Is IP address in CIDR block?
	 */
	public static function ipMatch(string $ip, string $mask): bool
	{
		[$mask, $size] = explode('/', $mask . '/');
		$tmp = function (int $n): string { return sprintf('%032b', $n); };
		$ip = implode('', array_map($tmp, unpack('N*', inet_pton($ip))));
		$mask = implode('', array_map($tmp, unpack('N*', inet_pton($mask))));
		$max = strlen($ip);
		if (!$max || $max !== strlen($mask) || (int) $size < 0 || (int) $size > $max) {
			return false;
		}
		return strncmp($ip, $mask, $size === '' ? $max : (int) $size) === 0;
	}
}
