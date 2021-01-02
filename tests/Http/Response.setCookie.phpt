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
		: ['Set-Cookie: test=value; path=/; SameSite=Lax; HttpOnly', 'Set-Cookie: test=newvalue; path=/; SameSite=Lax; HttpOnly']
, $headers);
