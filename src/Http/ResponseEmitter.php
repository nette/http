<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


/**
 * Sends a Http\Response for a PHP SAPI environment.
 */
final class ResponseEmitter
{
	use Nette\SmartObject;

	/** @var bool Whether warn on possible problem with data in output buffer */
	public $warnOnBuffer = true;

	/** @var bool  Send invisible garbage for IE? */
	public $fixIE = true;


	public function send(IResponse $response)
	{
		$this->sendHeaders($response);
		$this->sendBody($response);
	}


	public function sendHeaders(IResponse $response): void
	{
		$this->checkHeaders();

		header('HTTP/' . $response->getProtocolVersion() . ' ' . $response->getCode() . ' ' . ($response->getReasonPhrase() ?: 'Unknown status'));

		foreach (headers_list() as $header) {
			header_remove(explode(':', $header)[0]);
		}

		foreach ($response->getHeaders() as $name => $values) {
			if (strcasecmp($name, 'Content-Length') === 0 && ini_get('zlib.output_compression')) {
				continue; // ignore, PHP bug #44164
			}
			foreach ($values as $value) {
				header($name . ': ' . $value, false);
			}
		}
	}


	public function sendBody(IResponse $response): void
	{
		$body = $response->getBody();
		if (is_string($body)) {
			echo $body;
		} else {
			flush();
			$body();
		}

		if (
			$this->fixIE
			&& strpos($_SERVER['HTTP_USER_AGENT'] ?? '', 'MSIE ') !== false
			&& in_array($response->getCode(), [400, 403, 404, 405, 406, 408, 409, 410, 500, 501, 505], true)
			&& preg_match('#^text/html(?:;|$)#', (string) $response->getHeader('Content-Type'))
		) {
			echo Nette\Utils\Random::generate(2000, " \t\r\n"); // sends invisible garbage for IE
		}
	}


	private function checkHeaders(): void
	{
		if (PHP_SAPI === 'cli') {
			// ok
		} elseif (headers_sent($file, $line)) {
			throw new Nette\InvalidStateException('Cannot send header after HTTP headers have been sent' . ($file ? " (output started at $file:$line)." : '.'));

		} elseif (
			$this->warnOnBuffer &&
			ob_get_length() &&
			!array_filter(ob_get_status(true), function (array $i): bool { return !$i['chunk_size']; })
		) {
			trigger_error('Possible problem: you are sending a HTTP header while already having some data in output buffer. Try Tracy\OutputDebugger or start session earlier.');
		}
	}
}
