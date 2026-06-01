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


// integer 0 is deprecated, but kept as a session cookie for BC
$response = new Http\Response;
$old = headers_list();
$response->setCookie('test', 'g', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=g; path=/; HttpOnly; SameSite=Lax'], $headers);
