<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;


/**
 * HTTP-specific tasks.
 */
class Context
{
	public function __construct(
		private readonly IRequest $request,
		private readonly IResponse $response,
	) {
	}


	/**
	 * Checks whether the response has been modified since the client's cached version.
	 * Sets Last-Modified and ETag headers if provided. Returns false and sends 304 Not Modified if unchanged.
	 */
	public function isModified(string|int|\DateTimeInterface|null $lastModified = null, ?string $etag = null): bool
	{
		if ($lastModified) {
			$this->response->setHeader('Last-Modified', Helpers::formatDate($lastModified));
		}

		if ($etag) {
			$this->response->setHeader('ETag', '"' . addslashes($etag) . '"');
		}

		$ifNoneMatch = $this->request->getHeader('If-None-Match');
		if ($ifNoneMatch === '*') {
			$match = true; // match, check if-modified-since

		} elseif ($ifNoneMatch !== null) {
			$etag = $this->response->getHeader('ETag');

			if ($etag === null || !str_contains(' ' . strtr($ifNoneMatch, ",\t", '  '), ' ' . $etag)) {
				return true;

			} else {
				$match = true; // match, check if-modified-since
			}
		}

		$ifModifiedSince = $this->request->getHeader('If-Modified-Since');
		if ($ifModifiedSince !== null) {
			$lastModified = $this->response->getHeader('Last-Modified');
			if ($lastModified !== null && strtotime($lastModified) <= strtotime($ifModifiedSince)) {
				$match = true;

			} else {
				return true;
			}
		}

		if (empty($match)) {
			return true;
		}

		$this->response->setCode(IResponse::S304_NotModified);
		return false;
	}


	public function getRequest(): IRequest
	{
		return $this->request;
	}


	public function getResponse(): IResponse
	{
		return $this->response;
	}
}
