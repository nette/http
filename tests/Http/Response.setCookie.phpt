<?php declare(strict_types=1);

/**
 * Test: Nette\Http\Response::setCookie().
 */

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Cookies are not available in CLI');
}


$old = headers_list();
$response = new Http\Response;


$response->setCookie('test', 'value', null);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=value; path=/; HttpOnly; SameSite=Lax'], $headers);


$response->setCookie('test', 'newvalue', null);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=value; path=/; HttpOnly; SameSite=Lax', 'Set-Cookie: test=newvalue; path=/; HttpOnly; SameSite=Lax'], $headers);


// cookiePath
$response = new Http\Response;
$response->cookiePath = '/foo';
$old = headers_list();
$response->setCookie('test', 'a', null);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=a; path=/foo; HttpOnly; SameSite=Lax'], $headers);

// cookiePath + path
$old = headers_list();
$response->setCookie('test', 'b', null, '/bar');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=b; path=/bar; HttpOnly; SameSite=Lax'], $headers);

// cookiePath + domain
$old = headers_list();
$response->setCookie('test', 'c', null, null, 'nette.org');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=c; path=/; domain=nette.org; HttpOnly; SameSite=Lax'], $headers);


// cookieDomain
$response = new Http\Response;
$response->cookieDomain = 'nette.org';
$old = headers_list();
$response->setCookie('test', 'd', null);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=d; path=/; domain=nette.org; HttpOnly; SameSite=Lax'], $headers);

// cookieDomain + path
$old = headers_list();
$response->setCookie('test', 'e', null, '/bar');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=e; path=/bar; HttpOnly; SameSite=Lax'], $headers);

// cookieDomain + domain
$old = headers_list();
$response->setCookie('test', 'f', null, null, 'example.org');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=f; path=/; domain=example.org; HttpOnly; SameSite=Lax'], $headers);


// a future expiration sets the expires attribute
$response = new Http\Response;
$old = headers_list();
$response->setCookie('test', 'value', 3600);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::match('Set-Cookie: test=value; expires=%a%; path=/; HttpOnly; SameSite=Lax', $headers[0]);


// the value is percent-encoded, the name is kept verbatim (incl. [] for array cookies)
$response = new Http\Response;
$old = headers_list();
$response->setCookie('arr[key]', 'a b;c', null);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: arr[key]=a%20b%3Bc; path=/; HttpOnly; SameSite=Lax'], $headers);


// an illegal or empty name is rejected (it cannot be encoded - PHP would not decode it back)
$response = new Http\Response;
Assert::exception(
	fn() => $response->setCookie('a;b', 'value', null),
	Nette\InvalidArgumentException::class,
);
Assert::exception(
	fn() => $response->setCookie('', 'value', null),
	Nette\InvalidArgumentException::class,
);

// the name is validated even on the deprecated integer 0 path (no attribute injection)
Assert::exception(
	function () use ($response) {
		@$response->setCookie('a; Domain=evil', 'value', 0);
	},
	Nette\InvalidArgumentException::class,
);

// path, domain and sameSite are validated too (no attribute injection)
Assert::exception(
	fn() => $response->setCookie('test', 'value', null, path: '/a; Domain=evil'),
	Nette\InvalidArgumentException::class,
);
Assert::exception(
	fn() => $response->setCookie('test', 'value', null, domain: 'evil.com; Secure'),
	Nette\InvalidArgumentException::class,
);
Assert::exception(
	fn() => $response->setCookie('test', 'value', null, sameSite: 'Lax; Domain=evil'),
	Nette\InvalidArgumentException::class,
);

// the resolved value is validated, so an injection via the cookiePath property is caught too
$response = new Http\Response;
$response->cookiePath = '/a; Domain=evil';
Assert::exception(
	fn() => $response->setCookie('test', 'value', null),
	Nette\InvalidArgumentException::class,
);


// integer 0 is deprecated, but kept as a session cookie for BC
$response = new Http\Response;
$old = headers_list();
$response->setCookie('test', 'g', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=g; path=/; HttpOnly; SameSite=Lax'], $headers);
