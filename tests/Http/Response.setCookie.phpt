<?php

/**
 * Test: Nette\Http\Response::setCookie().
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Cookies are not available in CLI');
}


$old = headers_list();
$response = new Http\Response;


$response->setCookie('test', 'value', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=value; path=/; HttpOnly; SameSite=Lax'], $headers);


$response->setCookie('test', 'newvalue', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=value; path=/; HttpOnly; SameSite=Lax', 'Set-Cookie: test=newvalue; path=/; HttpOnly; SameSite=Lax'], $headers);


// cookiePath
$response = new Http\Response;
$response->cookiePath = '/foo';
$old = headers_list();
$response->setCookie('test', 'a', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=a; path=/foo; HttpOnly; SameSite=Lax'], $headers);

// cookiePath + path
$old = headers_list();
$response->setCookie('test', 'b', 0, '/bar');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=b; path=/bar; HttpOnly; SameSite=Lax'], $headers);

// cookiePath + domain
$old = headers_list();
$response->setCookie('test', 'c', 0, null, 'nette.org');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=c; path=/; domain=nette.org; HttpOnly; SameSite=Lax'], $headers);


// cookieDomain
$response = new Http\Response;
$response->cookieDomain = 'nette.org';
$old = headers_list();
$response->setCookie('test', 'd', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=d; path=/; domain=nette.org; HttpOnly; SameSite=Lax'], $headers);

// cookieDomain + path
$old = headers_list();
$response->setCookie('test', 'e', 0, '/bar');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=e; path=/bar; HttpOnly; SameSite=Lax'], $headers);

// cookieDomain + domain
$old = headers_list();
$response->setCookie('test', 'f', 0, null, 'example.org');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(['Set-Cookie: test=f; path=/; domain=example.org; HttpOnly; SameSite=Lax'], $headers);
