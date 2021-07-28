<?php

/**
 * Test: Nette\Http\Request headers.
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$request = new Http\Request(new Http\UrlScript);
	Assert::same([], $request->getHeaders());
});

test('', function () {
	$request = new Http\Request(new Http\UrlScript);
	Assert::same([], $request->getHeaders());
});

test('', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'one' => '1',
		'TWO' => '2',
		'X-Header' => 'X',
	]);

	Assert::same([
		'one' => '1',
		'two' => '2',
		'x-header' => 'X',
	], $request->getHeaders());
	Assert::same('1', $request->getHeader('One'));
	Assert::same('2', $request->getHeader('Two'));
	Assert::same('X', $request->getHeader('X-Header'));
});
