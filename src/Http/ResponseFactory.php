<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


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
		$response->setProtocolVersion(explode('/', $protocol)[1]);
		$this->parseHeaders($response, headers_list());
		return $response;
	}


	private function parseHeaders(Response $response, array $headers): void
	{
		foreach ($headers as $header) {
			$parts = explode(': ', $header, 2);
			$response->addHeader($parts[0], $parts[1]);
		}
	}
}
