<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Http;

use Nette,
	Nette\Utils\DateTime;


/**
 * Rendering helpers for HTTP.
 *
 * @author     David Grudl
 */
class Helpers
{

	/**
	 * Returns HTTP valid date format.
	 * @param  string|int|\DateTime
	 * @return string
	 */
	public static function formatDate($time)
	{
		$time = DateTime::from($time);
		$time->setTimezone(new \DateTimeZone('GMT'));
		return $time->format('D, d M Y H:i:s \G\M\T');
	}


	/**
	 * Is IP address in CIDR block?
	 * @return bool
	 */
	public static function ipMatch($ip, $mask)
	{
		list($mask, $size) = explode('/', $mask . '/');
		$ipv4 = strpos($ip, '.');
		$max = $ipv4 ? 32 : 128;
		if (($ipv4 xor strpos($mask, '.')) || $size < 0 || $size > $max) {
			return FALSE;
		} elseif ($ipv4) {
			$arr = array(ip2long($ip), ip2long($mask));
		} else {
			$arr = unpack('N*', inet_pton($ip) . inet_pton($mask));
			$size = $size === '' ? 0 : $max - $size;
		}
		$bits = implode('', array_map(function ($n) {
			return sprintf('%032b', $n);
		}, $arr));
		return substr($bits, 0, $max - $size) === substr($bits, $max, $max - $size);
	}


	/**
	 * Removes duplicate cookies from response.
	 * @return void
	 * @internal
	 */
	public static function removeDuplicateCookies()
	{
		if (headers_sent($file, $line) || ini_get('suhosin.cookie.encrypt')) {
			return;
		}

		$flatten = array();
		foreach (headers_list() as $header) {
			if (preg_match('#^Set-Cookie: .+?=#', $header, $m)) {
				$flatten[$m[0]] = $header;
				header_remove('Set-Cookie');
			}
		}
		foreach (array_values($flatten) as $key => $header) {
			header($header, $key === 0);
		}
	}


	/**
	 * @internal
	 */
	public static function stripSlashes($arr, $onlyKeys = FALSE)
	{
		$res = array();
		foreach ($arr as $k => $v) {
			$res[stripslashes($k)] = is_array($v)
				? self::stripSlashes($v, $onlyKeys)
				: ($onlyKeys ? $v : stripslashes($v));
		}
		return $res;
	}

}
