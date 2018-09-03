<?php

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


$response->setCookie('test', 'value', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
$headers = str_replace('HttpOnly', 'httponly', $headers);
Assert::same([
	'Set-Cookie: test=value; path=/; httponly',
], $headers);


$response->setCookie('test', 'newvalue', 0);
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
$headers = str_replace('HttpOnly', 'httponly', $headers);
Assert::same([
	'Set-Cookie: test=value; path=/; httponly',
	'Set-Cookie: test=newvalue; path=/; httponly',
], $headers);


$response->setCookie('test', 'newvalue', 0, null, null, null, null, 'Lax');
$headers = array_values(array_diff(headers_list(), $old, ['Set-Cookie:']));
$headers = str_replace('httponly', 'HttpOnly', $headers);
Assert::same([
	'Set-Cookie: test=value; path=/; HttpOnly',
	'Set-Cookie: test=newvalue; path=/; HttpOnly',
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: test=newvalue; path=/; HttpOnly; SameSite=Lax'
		: 'Set-Cookie: test=newvalue; path=/; SameSite=Lax; HttpOnly',
], $headers);
