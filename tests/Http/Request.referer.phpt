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

	Assert::null($request->getReferer());
});


test('', function () {
	$request = new Http\Request(new Http\UrlScript, null, null, null, [
		'referer' => 'http://nette.org:8080/file.php?q=search',
	]);

	Assert::same('http://nette.org:8080/file.php?q=search', $request->getReferer()->getAbsoluteUrl());
});


test('', function () {
	$request = new Http\Request(new Http\UrlScript, null, null, null, [
		'referer' => '/////',
	]);

	Assert::error(function () use ($request) {
		Assert::null($request->getReferer());
	}, E_USER_NOTICE, 'Unable to parse Malformed Referer URI: /////');
});
