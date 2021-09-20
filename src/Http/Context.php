<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


/**
 * HTTP-specific tasks.
 */
class Context
{
	use Nette\SmartObject;

	private IRequest $request;

	private IResponse $response;


	public function __construct(IRequest $request, IResponse $response)
	{
		$this->request = $request;
		$this->response = $response;
	}


	/**
	 * Attempts to cache the sent entity by its last modification date.
	 */
	public function isModified(string|int|\DateTimeInterface $lastModified = null, string $etag = null): bool
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

			if ($etag === null || strpos(' ' . strtr($ifNoneMatch, ",\t", '  '), ' ' . $etag) === false) {
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

		$this->response->setCode(IResponse::S304_NOT_MODIFIED);
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
