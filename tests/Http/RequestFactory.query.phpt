<?php

/**
 * Test: Nette\Http\RequestFactory query detection.
 */

declare(strict_types=1);

use Nette\Http\RequestFactory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$factory = new RequestFactory;

test('base URL without query parameters', function () use ($factory) {
	$_SERVER = [
		'HTTP_HOST' => 'nette.org',
		'REQUEST_URI' => '/',
	];

	Assert::same('http://nette.org/', (string) $factory->fromGlobals()->getUrl());
});


test('URL with query parameters', function () use ($factory) {
	$_SERVER = [
		'HTTP_HOST' => 'nette.org',
		'REQUEST_URI' => '/?a=b',
	];

	Assert::same('http://nette.org/?a=b', (string) $factory->fromGlobals()->getUrl());
});
