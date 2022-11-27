<?php

declare(strict_types=1);

namespace PHPSTORM_META;

registerArgumentsSet('nette_http_codes',
	\Nette\Http\IResponse::S100_Continue,
	\Nette\Http\IResponse::S101_SwitchingProtocols,
	\Nette\Http\IResponse::S102_Processing,
	\Nette\Http\IResponse::S200_OK,
	\Nette\Http\IResponse::S201_Created,
	\Nette\Http\IResponse::S202_Accepted,
	\Nette\Http\IResponse::S203_NonAuthoritativeInformation,
	\Nette\Http\IResponse::S204_NoContent,
	\Nette\Http\IResponse::S205_ResetContent,
	\Nette\Http\IResponse::S206_PartialContent,
	\Nette\Http\IResponse::S207_MultiStatus,
	\Nette\Http\IResponse::S208_AlreadyReported,
	\Nette\Http\IResponse::S226_ImUsed,
	\Nette\Http\IResponse::S300_MultipleChoices,
	\Nette\Http\IResponse::S301_MovedPermanently,
	\Nette\Http\IResponse::S302_Found,
	\Nette\Http\IResponse::S303_SeeOther,
	\Nette\Http\IResponse::S303_PostGet,
	\Nette\Http\IResponse::S304_NotModified,
	\Nette\Http\IResponse::S305_UseProxy,
	\Nette\Http\IResponse::S307_TemporaryRedirect,
	\Nette\Http\IResponse::S308_PermanentRedirect,
	\Nette\Http\IResponse::S400_BadRequest,
	\Nette\Http\IResponse::S401_Unauthorized,
	\Nette\Http\IResponse::S402_PaymentRequired,
	\Nette\Http\IResponse::S403_Forbidden,
	\Nette\Http\IResponse::S404_NotFound,
	\Nette\Http\IResponse::S405_MethodNotAllowed,
	\Nette\Http\IResponse::S406_NotAcceptable,
	\Nette\Http\IResponse::S407_ProxyAuthenticationRequired,
	\Nette\Http\IResponse::S408_RequestTimeout,
	\Nette\Http\IResponse::S409_Conflict,
	\Nette\Http\IResponse::S410_Gone,
	\Nette\Http\IResponse::S411_LengthRequired,
	\Nette\Http\IResponse::S412_PreconditionFailed,
	\Nette\Http\IResponse::S413_RequestEntityTooLarge,
	\Nette\Http\IResponse::S414_RequestUriTooLong,
	\Nette\Http\IResponse::S415_UnsupportedMediaType,
	\Nette\Http\IResponse::S416_RequestedRangeNotSatisfiable,
	\Nette\Http\IResponse::S417_ExpectationFailed,
	\Nette\Http\IResponse::S421_MisdirectedRequest,
	\Nette\Http\IResponse::S422_UnprocessableEntity,
	\Nette\Http\IResponse::S423_Locked,
	\Nette\Http\IResponse::S424_FailedDependency,
	\Nette\Http\IResponse::S426_UpgradeRequired,
	\Nette\Http\IResponse::S428_PreconditionRequired,
	\Nette\Http\IResponse::S429_TooManyRequests,
	\Nette\Http\IResponse::S431_RequestHeaderFieldsTooLarge,
	\Nette\Http\IResponse::S451_UnavailableForLegalReasons,
	\Nette\Http\IResponse::S500_InternalServerError,
	\Nette\Http\IResponse::S501_NotImplemented,
	\Nette\Http\IResponse::S502_BadGateway,
	\Nette\Http\IResponse::S503_ServiceUnavailable,
	\Nette\Http\IResponse::S504_GatewayTimeout,
	\Nette\Http\IResponse::S505_HttpVersionNotSupported,
	\Nette\Http\IResponse::S506_VariantAlsoNegotiates,
	\Nette\Http\IResponse::S507_InsufficientStorage,
	\Nette\Http\IResponse::S508_LoopDetected,
	\Nette\Http\IResponse::S510_NotExtended,
	\Nette\Http\IResponse::S511_NetworkAuthenticationRequired
);

registerArgumentsSet('nette_same_site',
	\Nette\Http\IResponse::SameSiteLax,
	\Nette\Http\IResponse::SameSiteStrict,
	\Nette\Http\IResponse::SameSiteNone
);

expectedArguments(\Nette\Http\IResponse::setCode(), 0, argumentsSet('nette_http_codes'));
expectedReturnValues(\Nette\Http\IResponse::getCode(), argumentsSet('nette_http_codes'));
expectedArguments(\Nette\Http\IResponse::setCookie(), 7, argumentsSet('nette_same_site'));
expectedArguments(\Nette\Http\Session::setCookieParameters(), 3, argumentsSet('nette_same_site'));
