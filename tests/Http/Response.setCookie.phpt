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
Assert::same([
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: test=value; path=/; HttpOnly; SameSite=Lax'
		: 'Set-Cookie: test=value; path=/; SameSite=Lax; HttpOnly',
], $headers);


$response->setCookie('test', 'newvalue', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same(
	PHP_VERSION_ID >= 70300
		? ['Set-Cookie: test=value; path=/; HttpOnly; SameSite=Lax', 'Set-Cookie: test=newvalue; path=/; HttpOnly; SameSite=Lax']
		: ['Set-Cookie: test=value; path=/; SameSite=Lax; HttpOnly', 'Set-Cookie: test=newvalue; path=/; SameSite=Lax; HttpOnly'],
	$headers
);


// cookiePath
$response = new Http\Response;
$response->cookiePath = '/foo';
$old = headers_list();
$response->setCookie('test', 'a', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same([
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: test=a; path=/foo; HttpOnly; SameSite=Lax'
		: 'Set-Cookie: test=a; path=/foo; SameSite=Lax; HttpOnly',
], $headers);

// cookiePath + path
$old = headers_list();
$response->setCookie('test', 'b', 0, '/bar');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same([
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: test=b; path=/bar; HttpOnly; SameSite=Lax'
		: 'Set-Cookie: test=b; path=/bar; SameSite=Lax; HttpOnly',
], $headers);

// cookiePath + domain
$old = headers_list();
$response->setCookie('test', 'c', 0, null, 'nette.org');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same([
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: test=c; path=/; domain=nette.org; HttpOnly; SameSite=Lax'
		: 'Set-Cookie: test=c; path=/; SameSite=Lax; domain=nette.org; HttpOnly',
], $headers);


// cookieDomain
$response = new Http\Response;
$response->cookieDomain = 'nette.org';
$old = headers_list();
$response->setCookie('test', 'd', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same([
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: test=d; path=/; domain=nette.org; HttpOnly; SameSite=Lax'
		: 'Set-Cookie: test=d; path=/; SameSite=Lax; domain=nette.org; HttpOnly',
], $headers);

// cookieDomain + path
$old = headers_list();
$response->setCookie('test', 'e', 0, '/bar');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same([
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: test=e; path=/bar; HttpOnly; SameSite=Lax'
		: 'Set-Cookie: test=e; path=/bar; SameSite=Lax; HttpOnly',
], $headers);

// cookieDomain + domain
$old = headers_list();
$response->setCookie('test', 'f', 0, null, 'example.org');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
Assert::same([
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: test=f; path=/; domain=example.org; HttpOnly; SameSite=Lax'
		: 'Set-Cookie: test=f; path=/; SameSite=Lax; domain=example.org; HttpOnly',
], $headers);
