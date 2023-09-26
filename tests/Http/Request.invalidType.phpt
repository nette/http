<?php

/**
 * Test: Nette\Http\Request invalid data.
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('invalid POST', function () {
	$_POST = [
		'int' => 1,
	];

	Assert::exception(
		fn() => (new Http\RequestFactory)->fromGlobals(),
		Nette\InvalidStateException::class,
		'Invalid value in $_POST/$_COOKIE in key \'int\', expected string, int given.',
	);
});


test('invalid COOKIE', function () {
	$_POST = [];
	$_COOKIE = ['x' => [1]];

	Assert::exception(
		fn() => (new Http\RequestFactory)->fromGlobals(),
		Nette\InvalidStateException::class,
		'Invalid value in $_POST/$_COOKIE in key \'0\', expected string, int given.',
	);
});
