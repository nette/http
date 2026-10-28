<?php declare(strict_types=1);

/**
 * Test: Nette\Http\Context::isModified() and ETag.
 */

use Nette\Http\{Context, IResponse, Request, UrlScript};
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function createResponse(): IResponse
{
	return new class implements IResponse {
		public int $code = self::S200_OK;

		/** @var array<string, string> */
		public array $headers = [];


		public function setCode(int $code, ?string $reason = null): static
		{
			$this->code = $code;
			return $this;
		}


		public function getCode(): int
		{
			return $this->code;
		}


		public function setHeader(string $name, string $value): static
		{
			$this->headers[$name] = $value;
			return $this;
		}


		public function addHeader(string $name, string $value): static
		{
			return $this->setHeader($name, $value);
		}


		public function setContentType(string $type, ?string $charset = null): static
		{
			return $this;
		}


		public function redirect(string $url, int $code = self::S302_Found): void
		{
		}


		public function setExpiration(?string $expire): static
		{
			return $this;
		}


		public function isSent(): bool
		{
			return false;
		}


		public function getHeader(string $header): ?string
		{
			return $this->headers[$header] ?? null;
		}


		public function getHeaders(): array
		{
			return [];
		}


		public function setCookie(
			string $name,
			string $value,
			string|int|DateTimeInterface|null $expire,
			?string $path = null,
			?string $domain = null,
			?bool $secure = null,
			?bool $httpOnly = null,
			mixed $sameSite = null,
			bool $partitioned = false,
		): static
		{
			return $this;
		}


		public function deleteCookie(string $name, ?string $path = null, ?string $domain = null, ?bool $secure = null)
		{
		}
	};
}


function isModified(?string $ifNoneMatch, ?string $etag = 'abc', ?string $responseEtag = null): bool
{
	$response = createResponse();
	if ($responseEtag !== null) {
		$response->setHeader('ETag', $responseEtag);
	}

	$request = new Request(new UrlScript, headers: $ifNoneMatch === null ? [] : ['If-None-Match' => $ifNoneMatch]);
	$res = (new Context($request, $response))->isModified(etag: $etag);
	Assert::same($res ? IResponse::S200_OK : IResponse::S304_NotModified, $response->getCode());
	return $res;
}


test('strong match', function () {
	Assert::false(isModified('"abc"'));
	Assert::false(isModified('"xyz", "abc"'));
	Assert::false(isModified('*'));
});


test('weak comparison', function () {
	Assert::false(isModified('W/"abc"'));
	Assert::false(isModified('"xyz",W/"abc"'));
	Assert::false(isModified('W/"abc"', null, 'W/"abc"'));
	Assert::false(isModified('"abc"', null, 'W/"abc"'));
});


test('no match', function () {
	Assert::true(isModified(null));
	Assert::true(isModified('"abcd"'));
	Assert::true(isModified('W/"xyz"'));
	Assert::true(isModified('"abc"', null));
});
