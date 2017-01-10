<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use Nette\Utils\DateTime;


/**
 * Rendering helpers for HTTP.
 */
class Helpers
{
	use Nette\StaticClass;

	/**
	 * Returns HTTP valid date format.
	 * @param  string|int|\DateTimeInterface
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
		$tmp = function ($n) { return sprintf('%032b', $n); };
		$ip = implode('', array_map($tmp, unpack('N*', inet_pton($ip))));
		$mask = implode('', array_map($tmp, unpack('N*', inet_pton($mask))));
		$max = strlen($ip);
		if (!$max || $max !== strlen($mask) || (int) $size < 0 || (int) $size > $max) {
			return FALSE;
		}
		return strncmp($ip, $mask, $size === '' ? $max : (int) $size) === 0;
	}


	public static function parseHeader($value)
	{
		$tokenizer = new Nette\Utils\Tokenizer([
			'word' => '[\\w!#$%&\'*+\\-.\\^`|\\~/]+', // "/" manually allowed

			'quoted' => '"(?:\\\\[\x01-\x7F]|[^"\\\\\x00\x80-\xFF])*+"',
			'equal' => '=',
			'semicolon' => ';',
			'ows' => '\h+',
		]);

		$tokens = $tokenizer->tokenize("$value;");
		$parsed = [];
		$k = NULL;
		$v = NULL;
		$e = FALSE;

		foreach ($tokens as list($value, $offset, $type)) {
			if ($type === 'word') {
				if ($e && $v === NULL) {
					$v = $value;
				} elseif ($k === NULL) {
					$k = $value;
				} else {
					throw new \Exception();
				}

			} elseif ($type === 'quoted') {
				$value = stripslashes(substr($value, 1, -1));
				if ($e && $v === NULL) {
					$v = $value;
				} else {
					throw new \Exception();
				}

			} elseif ($type === 'semicolon') {
				if ($k === NULL) {
					throw new \Exception();
				}
				$parsed[$k] = $v;
				$k = $v = NULL;
				$e = FALSE;

			} elseif ($type === 'equal') {
				if ($e || $k === NULL) {
					throw new \Exception();
				}

				$e = TRUE;
			}
		}

		return $parsed;
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

		$flatten = [];
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

}
