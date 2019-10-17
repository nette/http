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
		$stream = fopen($url, 'r') ?: throw new Nette\IOException("Unable to open '$url'.");
		$response->setBody((string) stream_get_contents($stream));
		$headers = [];
		foreach (stream_get_meta_data($stream)['wrapper_data'] ?? [] as $header) {
			if (str_starts_with($header, 'HTTP/')) {
				$headers = [];
			}
			$headers[] = $header;
		}

		fclose($stream);
		$status = array_shift($headers) ?? throw new Nette\InvalidArgumentException("'$url' did not return an HTTP response.");
		$this->parseStatus($response, $status);
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
