<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use function ini_get, is_string;
use const PHP_SAPI;


/**
 * Sends a Http\Response for a PHP SAPI environment.
 */
final class ResponseEmitter
{
	use Nette\SmartObject;

	/** Whether to warn when there is data in the output buffer before sending headers */
	public bool $warnOnBuffer = true;


	public function send(Response $response): void
	{
		$this->sendHeaders($response);
		$this->sendBody($response);
	}


	public function sendHeaders(Response $response): void
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
				header($name . ': ' . $value, replace: false);
			}
		}
	}


	public function sendBody(Response $response): void
	{
		$body = $response->getBody();
		if (is_string($body)) {
			echo $body;
		} else {
			flush();
			$body();
		}
	}


	private function checkHeaders(): void
	{
		if (PHP_SAPI === 'cli') {
		} elseif (headers_sent($file, $line)) {
			throw new Nette\InvalidStateException('Cannot send header after HTTP headers have been sent' . ($file ? " (output started at $file:$line)." : '.'));

		} elseif (
			$this->warnOnBuffer
			&& ob_get_length()
			&& !array_filter(ob_get_status(full_status: true), fn(array $i): bool => !$i['chunk_size'])
		) {
			trigger_error('Possible problem: you are sending a HTTP header while already having some data in output buffer. Try Tracy\OutputDebugger or send cookies/start session earlier.');
		}
	}
}
