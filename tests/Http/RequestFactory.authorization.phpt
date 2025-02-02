<?php

/**
 * Test: Nette\Http\RequestFactory and Authorization header
 */

declare(strict_types=1);

use Nette\Http\RequestFactory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('basic authentication via PHP_AUTH_* variables', function () {
	$_SERVER = [
		'PHP_AUTH_USER' => 'user',
		'PHP_AUTH_PW' => 'password',
	];

	$factory = new RequestFactory;
	$request = $factory->fromGlobals();
	Assert::same(
		'Basic dXNlcjpwYXNzd29yZA==',
		$request->getHeader('Authorization'),
	);
	Assert::same(['user', 'password'], $request->getBasicCredentials());

	Assert::same('', $request->getUrl()->getUser());
	Assert::same('', $request->getUrl()->getPassword());
});



test('digest authentication header parsing', function () {
	$_SERVER = [
		'PHP_AUTH_DIGEST' => 'username="admin"',
	];

	$factory = new RequestFactory;
	$request = $factory->fromGlobals();
	Assert::same(
		'Digest username="admin"',
		$request->getHeader('Authorization'),
	);
	Assert::null($request->getBasicCredentials());
});


test('absence of authentication headers', function () {
	$_SERVER = [];
	$factory = new RequestFactory;
	$request = $factory->fromGlobals();
	Assert::null($request->getBasicCredentials());
});
