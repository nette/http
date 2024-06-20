<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;


/**
 * HTTP response interface.
 * @method self deleteHeader(string $name)
 */
interface IResponse
{
	/** HTTP 1.1 response code */
	public const
		S100_Continue = 100,
		S101_SwitchingProtocols = 101,
		S102_Processing = 102,
		S200_OK = 200,
		S201_Created = 201,
		S202_Accepted = 202,
		S203_NonAuthoritativeInformation = 203,
		S204_NoContent = 204,
		S205_ResetContent = 205,
		S206_PartialContent = 206,
		S207_MultiStatus = 207,
		S208_AlreadyReported = 208,
		S226_ImUsed = 226,
		S300_MultipleChoices = 300,
		S301_MovedPermanently = 301,
		S302_Found = 302,
		S303_PostGet = 303,
		S304_NotModified = 304,
		S305_UseProxy = 305,
		S307_TemporaryRedirect = 307,
		S308_PermanentRedirect = 308,
		S400_BadRequest = 400,
		S401_Unauthorized = 401,
		S402_PaymentRequired = 402,
		S403_Forbidden = 403,
		S404_NotFound = 404,
		S405_MethodNotAllowed = 405,
		S406_NotAcceptable = 406,
		S407_ProxyAuthenticationRequired = 407,
		S408_RequestTimeout = 408,
		S409_Conflict = 409,
		S410_Gone = 410,
		S411_LengthRequired = 411,
		S412_PreconditionFailed = 412,
		S413_RequestEntityTooLarge = 413,
		S414_RequestUriTooLong = 414,
		S415_UnsupportedMediaType = 415,
		S416_RequestedRangeNotSatisfiable = 416,
		S417_ExpectationFailed = 417,
		S421_MisdirectedRequest = 421,
		S422_UnprocessableEntity = 422,
		S423_Locked = 423,
		S424_FailedDependency = 424,
		S426_UpgradeRequired = 426,
		S428_PreconditionRequired = 428,
		S429_TooManyRequests = 429,
		S431_RequestHeaderFieldsTooLarge = 431,
		S451_UnavailableForLegalReasons = 451,
		S500_InternalServerError = 500,
		S501_NotImplemented = 501,
		S502_BadGateway = 502,
		S503_ServiceUnavailable = 503,
		S504_GatewayTimeout = 504,
		S505_HttpVersionNotSupported = 505,
		S506_VariantAlsoNegotiates = 506,
		S507_InsufficientStorage = 507,
		S508_LoopDetected = 508,
		S510_NotExtended = 510,
		S511_NetworkAuthenticationRequired = 511;

	public const ReasonPhrases = [
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Time-out',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Large',
		415 => 'Unsupported Media Type',
		416 => 'Requested range not satisfiable',
		417 => 'Expectation Failed',
		421 => 'Misdirected Request',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Time-out',
		505 => 'HTTP Version not supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
	];

	/** SameSite cookie */
	public const
		SameSiteLax = 'Lax',
		SameSiteStrict = 'Strict',
		SameSiteNone = 'None';

	/** @deprecated use IResponse::ReasonPhrases */
	public const REASON_PHRASES = self::ReasonPhrases;

	/** @deprecated use IResponse::SameSiteLax */
	public const SAME_SITE_LAX = self::SameSiteLax;

	/** @deprecated use IResponse::SameSiteStrict */
	public const SAME_SITE_STRICT = self::SameSiteStrict;

	/** @deprecated use IResponse::SameSiteNone */
	public const SAME_SITE_NONE = self::SameSiteNone;

	/** @deprecated use IResponse::S100_Continue */
	public const S100_CONTINUE = self::S100_Continue;

	/** @deprecated use IResponse::S101_SwitchingProtocols */
	public const S101_SWITCHING_PROTOCOLS = self::S101_SwitchingProtocols;

	/** @deprecated use IResponse::S102_Processing */
	public const S102_PROCESSING = self::S102_Processing;

	/** @deprecated use IResponse::S201_Created */
	public const S201_CREATED = self::S201_Created;

	/** @deprecated use IResponse::S202_Accepted */
	public const S202_ACCEPTED = self::S202_Accepted;

	/** @deprecated use IResponse::S203_NonAuthoritativeInformation */
	public const S203_NON_AUTHORITATIVE_INFORMATION = self::S203_NonAuthoritativeInformation;

	/** @deprecated use IResponse::S204_NoContent */
	public const S204_NO_CONTENT = self::S204_NoContent;

	/** @deprecated use IResponse::S205_ResetContent */
	public const S205_RESET_CONTENT = self::S205_ResetContent;

	/** @deprecated use IResponse::S206_PartialContent */
	public const S206_PARTIAL_CONTENT = self::S206_PartialContent;

	/** @deprecated use IResponse::S207_MultiStatus */
	public const S207_MULTI_STATUS = self::S207_MultiStatus;

	/** @deprecated use IResponse::S208_AlreadyReported */
	public const S208_ALREADY_REPORTED = self::S208_AlreadyReported;

	/** @deprecated use IResponse::S226_ImUsed */
	public const S226_IM_USED = self::S226_ImUsed;

	/** @deprecated use IResponse::S300_MultipleChoices */
	public const S300_MULTIPLE_CHOICES = self::S300_MultipleChoices;

	/** @deprecated use IResponse::S301_MovedPermanently */
	public const S301_MOVED_PERMANENTLY = self::S301_MovedPermanently;

	/** @deprecated use IResponse::S302_Found */
	public const S302_FOUND = self::S302_Found;

	/** @deprecated use IResponse::S303_PostGet */
	public const S303_SEE_OTHER = self::S303_PostGet;

	/** @deprecated use IResponse::S303_PostGet */
	public const S303_POST_GET = self::S303_PostGet;

	/** @deprecated use IResponse::S304_NotModified */
	public const S304_NOT_MODIFIED = self::S304_NotModified;

	/** @deprecated use IResponse::S305_UseProxy */
	public const S305_USE_PROXY = self::S305_UseProxy;

	/** @deprecated use IResponse::S307_TemporaryRedirect */
	public const S307_TEMPORARY_REDIRECT = self::S307_TemporaryRedirect;

	/** @deprecated use IResponse::S308_PermanentRedirect */
	public const S308_PERMANENT_REDIRECT = self::S308_PermanentRedirect;

	/** @deprecated use IResponse::S400_BadRequest */
	public const S400_BAD_REQUEST = self::S400_BadRequest;

	/** @deprecated use IResponse::S401_Unauthorized */
	public const S401_UNAUTHORIZED = self::S401_Unauthorized;

	/** @deprecated use IResponse::S402_PaymentRequired */
	public const S402_PAYMENT_REQUIRED = self::S402_PaymentRequired;

	/** @deprecated use IResponse::S403_Forbidden */
	public const S403_FORBIDDEN = self::S403_Forbidden;

	/** @deprecated use IResponse::S404_NotFound */
	public const S404_NOT_FOUND = self::S404_NotFound;

	/** @deprecated use IResponse::S405_MethodNotAllowed */
	public const S405_METHOD_NOT_ALLOWED = self::S405_MethodNotAllowed;

	/** @deprecated use IResponse::S406_NotAcceptable */
	public const S406_NOT_ACCEPTABLE = self::S406_NotAcceptable;

	/** @deprecated use IResponse::S407_ProxyAuthenticationRequired */
	public const S407_PROXY_AUTHENTICATION_REQUIRED = self::S407_ProxyAuthenticationRequired;

	/** @deprecated use IResponse::S408_RequestTimeout */
	public const S408_REQUEST_TIMEOUT = self::S408_RequestTimeout;

	/** @deprecated use IResponse::S409_Conflict */
	public const S409_CONFLICT = self::S409_Conflict;

	/** @deprecated use IResponse::S410_Gone */
	public const S410_GONE = self::S410_Gone;

	/** @deprecated use IResponse::S411_LengthRequired */
	public const S411_LENGTH_REQUIRED = self::S411_LengthRequired;

	/** @deprecated use IResponse::S412_PreconditionFailed */
	public const S412_PRECONDITION_FAILED = self::S412_PreconditionFailed;

	/** @deprecated use IResponse::S413_RequestEntityTooLarge */
	public const S413_REQUEST_ENTITY_TOO_LARGE = self::S413_RequestEntityTooLarge;

	/** @deprecated use IResponse::S414_RequestUriTooLong */
	public const S414_REQUEST_URI_TOO_LONG = self::S414_RequestUriTooLong;

	/** @deprecated use IResponse::S415_UnsupportedMediaType */
	public const S415_UNSUPPORTED_MEDIA_TYPE = self::S415_UnsupportedMediaType;

	/** @deprecated use IResponse::S416_RequestedRangeNotSatisfiable */
	public const S416_REQUESTED_RANGE_NOT_SATISFIABLE = self::S416_RequestedRangeNotSatisfiable;

	/** @deprecated use IResponse::S417_ExpectationFailed */
	public const S417_EXPECTATION_FAILED = self::S417_ExpectationFailed;

	/** @deprecated use IResponse::S421_MisdirectedRequest */
	public const S421_MISDIRECTED_REQUEST = self::S421_MisdirectedRequest;

	/** @deprecated use IResponse::S422_UnprocessableEntity */
	public const S422_UNPROCESSABLE_ENTITY = self::S422_UnprocessableEntity;

	/** @deprecated use IResponse::S423_Locked */
	public const S423_LOCKED = self::S423_Locked;

	/** @deprecated use IResponse::S424_FailedDependency */
	public const S424_FAILED_DEPENDENCY = self::S424_FailedDependency;

	/** @deprecated use IResponse::S426_UpgradeRequired */
	public const S426_UPGRADE_REQUIRED = self::S426_UpgradeRequired;

	/** @deprecated use IResponse::S428_PreconditionRequired */
	public const S428_PRECONDITION_REQUIRED = self::S428_PreconditionRequired;

	/** @deprecated use IResponse::S429_TooManyRequests */
	public const S429_TOO_MANY_REQUESTS = self::S429_TooManyRequests;

	/** @deprecated use IResponse::S431_RequestHeaderFieldsTooLarge */
	public const S431_REQUEST_HEADER_FIELDS_TOO_LARGE = self::S431_RequestHeaderFieldsTooLarge;

	/** @deprecated use IResponse::S451_UnavailableForLegalReasons */
	public const S451_UNAVAILABLE_FOR_LEGAL_REASONS = self::S451_UnavailableForLegalReasons;

	/** @deprecated use IResponse::S500_InternalServerError */
	public const S500_INTERNAL_SERVER_ERROR = self::S500_InternalServerError;

	/** @deprecated use IResponse::S501_NotImplemented */
	public const S501_NOT_IMPLEMENTED = self::S501_NotImplemented;

	/** @deprecated use IResponse::S502_BadGateway */
	public const S502_BAD_GATEWAY = self::S502_BadGateway;

	/** @deprecated use IResponse::S503_ServiceUnavailable */
	public const S503_SERVICE_UNAVAILABLE = self::S503_ServiceUnavailable;

	/** @deprecated use IResponse::S504_GatewayTimeout */
	public const S504_GATEWAY_TIMEOUT = self::S504_GatewayTimeout;

	/** @deprecated use IResponse::S505_HttpVersionNotSupported */
	public const S505_HTTP_VERSION_NOT_SUPPORTED = self::S505_HttpVersionNotSupported;

	/** @deprecated use IResponse::S506_VariantAlsoNegotiates */
	public const S506_VARIANT_ALSO_NEGOTIATES = self::S506_VariantAlsoNegotiates;

	/** @deprecated use IResponse::S507_InsufficientStorage */
	public const S507_INSUFFICIENT_STORAGE = self::S507_InsufficientStorage;

	/** @deprecated use IResponse::S508_LoopDetected */
	public const S508_LOOP_DETECTED = self::S508_LoopDetected;

	/** @deprecated use IResponse::S510_NotExtended */
	public const S510_NOT_EXTENDED = self::S510_NotExtended;

	/** @deprecated use IResponse::S511_NetworkAuthenticationRequired */
	public const S511_NETWORK_AUTHENTICATION_REQUIRED = self::S511_NetworkAuthenticationRequired;

	/**
	 * Sets HTTP response code.
	 * @return static
	 */
	function setCode(int $code, ?string $reason = null);

	/**
	 * Returns HTTP response code.
	 */
	function getCode(): int;

	/**
	 * Sends a HTTP header and replaces a previous one.
	 * @return static
	 */
	function setHeader(string $name, string $value);

	/**
	 * Adds HTTP header.
	 * @return static
	 */
	function addHeader(string $name, string $value);

	/**
	 * Sends a Content-type HTTP header.
	 * @return static
	 */
	function setContentType(string $type, ?string $charset = null);

	/**
	 * Redirects to a new URL.
	 */
	function redirect(string $url, int $code = self::S302_Found): void;

	/**
	 * Sets the time (like '20 minutes') before a page cached on a browser expires, null means "must-revalidate".
	 * @return static
	 */
	function setExpiration(?string $expire);

	/**
	 * Checks if headers have been sent.
	 */
	function isSent(): bool;

	/**
	 * Returns value of an HTTP header.
	 */
	function getHeader(string $header): ?string;

	/**
	 * Returns an associative array of headers to sent.
	 */
	function getHeaders(): array;

	/**
	 * Sends a cookie.
	 * @return static
	 */
	function setCookie(
		string $name,
		string $value,
		string|int|\DateTimeInterface|null $expire,
		?string $path = null,
		?string $domain = null,
		?bool $secure = null,
		?bool $httpOnly = null,
	);

	/**
	 * Deletes a cookie.
	 */
	function deleteCookie(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null);
}
