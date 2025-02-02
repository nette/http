<?php

/**
 * Test: Nette\Http\Request invalid data.
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('non-string type in POST data', function () {
	$_POST = [
		'int' => 1,
	];

	Assert::exception(
		fn() => (new Http\RequestFactory)->fromGlobals(),
		Nette\InvalidStateException::class,
		'Invalid value in $_POST/$_COOKIE in key \'int\', expected string, int given.',
	);
});


test('non-string type in cookie array', function () {
	$_POST = [];
	$_COOKIE = ['x' => [1]];

	Assert::exception(
		fn() => (new Http\RequestFactory)->fromGlobals(),
		Nette\InvalidStateException::class,
		'Invalid value in $_POST/$_COOKIE in key \'0\', expected string, int given.',
	);
});
