<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use function is_int;


/**
 * HTTP response factories.
 */
final class ResponseFactory
{
	use Nette\SmartObject;

	public function fromGlobals(): Response
	{
		$response = new Response;
		if (is_int($code = http_response_code())) {
			$response->setCode($code);
		}

		$protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
		$response->setProtocolVersion(explode('/', $protocol)[1] ?? '1.1');
		$this->parseHeaders($response, headers_list());
		return $response;
	}


	/**
	 * @param  string[]  $headers
	 */
	private function parseHeaders(Response $response, array $headers): void
	{
		foreach ($headers as $header) {
			$parts = explode(':', $header, 2);
			if (isset($parts[1])) {
				$response->addHeader($parts[0], ltrim($parts[1]));
			}
		}
	}
}
