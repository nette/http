<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;


/**
 * HTTP request contract providing access to URL, headers, cookies, uploaded files, and body.
 * @method ?UrlImmutable getReferer() Returns the referrer URL.
 * @method bool isSameSite() Checks whether the request is coming from the same site.
 * @method bool isFrom(string|list<string>|null $site = null, string|list<string>|null $initiator = null)
 */
interface IRequest
{
	/** HTTP request method */
	public const
		Get = 'GET',
		Post = 'POST',
		Head = 'HEAD',
		Put = 'PUT',
		Delete = 'DELETE',
		Patch = 'PATCH',
		Options = 'OPTIONS';

	/** @deprecated use IRequest::Get */
	public const GET = self::Get;

	/** @deprecated use IRequest::Post */
	public const POST = self::Post;

	/** @deprecated use IRequest::Head */
	public const HEAD = self::Head;

	/** @deprecated use IRequest::Put */
	public const PUT = self::Put;

	/** @deprecated use IRequest::Delete */
	public const DELETE = self::Delete;

	/** @deprecated use IRequest::Patch */
	public const PATCH = self::Patch;

	/** @deprecated use IRequest::Options */
	public const OPTIONS = self::Options;

	/**
	 * Returns the request URL.
	 */
	function getUrl(): UrlScript;

	/********************* query, post, files & cookies ****************d*g**/

	/**
	 * Returns a URL query parameter, or all parameters as an array if no key is given.
	 * @return mixed
	 */
	function getQuery(?string $key = null);

	/**
	 * Returns a POST parameter, or all POST parameters as an array if no key is given.
	 * @return mixed
	 */
	function getPost(?string $key = null);

	/**
	 * Returns the uploaded file for the given key, or null if not present.
	 * Accepts a string key or an array of keys for nested file structures (e.g. ['form', 'avatar']).
	 * @return ?FileUpload
	 */
	function getFile(string $key);

	/**
	 * Returns the tree of uploaded files, with each leaf being a FileUpload instance.
	 * @return mixed[]
	 */
	function getFiles(): array;

	/**
	 * Returns a cookie value, or null if it does not exist.
	 * @return mixed
	 */
	function getCookie(string $key);

	/**
	 * Returns all cookies.
	 * @return array<string, string>
	 */
	function getCookies(): array;

	/********************* method & headers ****************d*g**/

	/**
	 * Returns the HTTP request method (GET, POST, HEAD, PUT, ...).
	 */
	function getMethod(): string;

	/**
	 * Checks the HTTP request method. The comparison is case-insensitive.
	 */
	function isMethod(string $method): bool;

	/**
	 * Returns the value of an HTTP header, or null if it does not exist. The name is case-insensitive.
	 */
	function getHeader(string $header): ?string;

	/**
	 * Returns all HTTP headers.
	 * @return array<string, string>
	 */
	function getHeaders(): array;

	/**
	 * Checks whether the request was sent via a secure channel (HTTPS).
	 */
	function isSecured(): bool;

	/**
	 * Checks whether the request was made via AJAX (X-Requested-With: XMLHttpRequest).
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
