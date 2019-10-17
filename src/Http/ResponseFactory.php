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


	public function fromString(string $message): Response
	{
		$response = new Response;
		$parts = explode("\r\n\r\n", $message, 2);
		$headers = explode("\r\n", $parts[0]);
		$this->parseStatus($response, array_shift($headers));
		$this->parseHeaders($response, $headers);
		$response->setBody($parts[1] ?? '');
		return $response;
	}


	public function fromUrl(string $url): Response
	{
		$response = new Response;
		$response->setBody(file_get_contents($url));
		$headers = [];
		foreach ($http_response_header as $header) {
			if (substr($header, 0, 5) === 'HTTP/') {
				$headers = [];
			}
			$headers[] = $header;
		}
		$this->parseStatus($response, array_shift($headers));
		$this->parseHeaders($response, $headers);
		return $response;
	}


	private function parseStatus(Response $response, string $status): void
	{
		if (!preg_match('#^HTTP/([\d.]+) (\d+) (.+)$#', $status, $m)) {
			throw new Nette\InvalidArgumentException("Invalid status line '$status'.");
		}
		$response->setProtocolVersion($m[1]);
		$response->setCode((int) $m[2], $m[3]);
	}


	private function parseHeaders(Response $response, array $headers): void
	{
		foreach ($headers as $header) {
			$parts = explode(': ', $header, 2);
			$response->addHeader($parts[0], $parts[1]);
		}
	}
}
