<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use Nette\Utils\DateTime;
use function array_shift, arsort, explode, is_int, preg_match, strtolower, trim;


/**
 * Helper functions for HTTP requests, responses and headers.
 */
final class Helpers
{
	use Nette\StaticClass;


	/**
	 * Formats a date and time in the HTTP date format (RFC 7231), e.g. 'Mon, 23 Jan 1978 10:00:00 GMT'.
	 */
	public static function formatDate(string|int|\DateTimeInterface $time): string
	{
		$time = DateTime::from($time)->setTimezone(new \DateTimeZone('GMT'));
		return $time->format('D, d M Y H:i:s \G\M\T');
	}


	/**
	 * Converts an expiration value to the number of seconds from now (may be negative for the past).
	 * Integers (or numeric strings) are relative seconds; other strings ('20 minutes', '2024-01-01')
	 * and DateTimeInterface are absolute times. Null returns null.
	 * @throws Nette\InvalidArgumentException  for an empty string
	 * @throws \DateMalformedStringException  for an unparsable textual time
	 */
	public static function expirationToSeconds(string|int|\DateTimeInterface|null $expire): ?int
	{
		return match (true) {
			$expire === null => null,
			is_int($expire) => $expire,
			$expire instanceof \DateTimeInterface => $expire->getTimestamp() - time(),
			$expire === '' => throw new Nette\InvalidArgumentException('Expiration must not be an empty string; use null instead.'),
			($seconds = filter_var($expire, FILTER_VALIDATE_INT)) !== false => $seconds,
			default => (new DateTime($expire))->getTimestamp() - time(),
		};
	}


	/**
	 * Parses an HTTP quality-value list such as the Accept, Accept-Language or Accept-Encoding header
	 * into tokens mapped to their q-factor, ordered by descending preference. Tokens are lowercased and
	 * those explicitly rejected with q=0 are omitted.
	 * @return array<string, float>  e.g. ['cs-cz' => 1.0, 'en' => 0.8]
	 */
	public static function parseQualityList(string $header): array
	{
		$list = [];
		foreach (explode(',', $header) as $item) {
			$params = explode(';', $item);
			$token = strtolower(trim((string) array_shift($params)));
			if ($token === '') {
				continue;
			}

			$q = 1.0;
			foreach ($params as $param) {
				if (preg_match('#^\s*q\s*=\s*([0-9.]+)#i', $param, $m)) {
					$q = min(1.0, (float) $m[1]); // q is capped at 1 per RFC 9110
				}
			}

			if ($q > 0) {
				$list[$token] = max($list[$token] ?? 0.0, $q); // a repeated token keeps its highest q
			}
		}

		arsort($list); // stable since PHP 8.0, so equal q keeps header order
		return $list;
	}


	/**
	 * Checks whether an IP address falls within a CIDR block (e.g. '192.168.1.0/24').
	 * @deprecated use IPAddress class
	 */
	public static function ipMatch(string $ip, string $mask): bool
	{
		return IPAddress::tryFrom($ip)?->isInRange($mask) ?? false;
	}
}
