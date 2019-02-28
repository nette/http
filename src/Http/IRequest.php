<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;


/**
 * IHttpRequest provides access scheme for request sent via HTTP.
 * @method UrlImmutable|null getReferer() Returns referrer.
 * @method bool isSameSite() Is the request sent from the same origin?
 */
interface IRequest
{
	/** HTTP request method */
	public const
		GET = 'GET',
		POST = 'POST',
		HEAD = 'HEAD',
		PUT = 'PUT',
		DELETE = 'DELETE',
		PATCH = 'PATCH',
		OPTIONS = 'OPTIONS';

	/**
	 * Returns URL object.
	 */
	function getUrl(): UrlScript;

	/********************* query, post, files & cookies ****************d*g**/

	/**
	 * Returns variable provided to the script via URL query ($_GET).
	 * If no key is passed, returns the entire array.
	 * @return mixed
	 */
	function getQuery(string $key = null);

	/**
	 * Returns variable provided to the script via POST method ($_POST).
	 * If no key is passed, returns the entire array.
	 * @return mixed
	 */
	function getPost(string $key = null);

	/**
	 * Returns uploaded file.
	 * @return FileUpload|array|null
	 */
	function getFile(string $key);

	/**
	 * Returns uploaded files.
	 */
	function getFiles(): array;

	/**
	 * Returns variable provided to the script via HTTP cookies.
	 * @return mixed
	 */
	function getCookie(string $key);

	/**
	 * Returns variables provided to the script via HTTP cookies.
	 */
	function getCookies(): array;

	/********************* method & headers ****************d*g**/

	/**
	 * Returns HTTP request method (GET, POST, HEAD, PUT, ...). The method is case-sensitive.
	 */
	function getMethod(): string;

	/**
	 * Checks HTTP request method.
	 */
	function isMethod(string $method): bool;

	/**
	 * Return the value of the HTTP header. Pass the header name as the
	 * plain, HTTP-specified header name (e.g. 'Accept-Encoding').
	 */
	function getHeader(string $header): ?string;

	/**
	 * Returns all HTTP headers.
	 */
	function getHeaders(): array;

	/**
	 * Is the request sent via secure channel (https)?
	 */
	function isSecured(): bool;

	/**
	 * Is AJAX request?
	 */
	function isAjax(): bool;

	/**
	 * Returns the IP address of the remote client.
	 */
	function getRemoteAddress(): ?string;

	/**
	 * Returns the host of the remote client.
	 */
	function getRemoteHost(): ?string;

	/**
	 * Returns raw content of HTTP request body.
	 */
	function getRawBody(): ?string;
}
