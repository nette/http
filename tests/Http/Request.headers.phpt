<?php

/**
 * Test: Nette\Http\Request headers.
 */

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$request = new Http\Request(new Http\UrlScript);
	Assert::same([], $request->getHeaders());
});

test(function () {
	$request = new Http\Request(new Http\UrlScript, NULL, NULL, NULL, NULL, []);
	Assert::same([], $request->getHeaders());
});

test(function () {
	$request = new Http\Request(new Http\UrlScript, NULL, NULL, NULL, NULL, [
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

test(function() {
	$emptyRequest = new Http\Request(new Http\UrlScript);
	Assert::null($emptyRequest->getHeader('referer'));
	Assert::null($emptyRequest->getReferrer());

	$referrerRequest = new Http\Request(new Http\UrlScript, NULL, NULL, NULL, NULL, array(
		'referer' => 'http://nette.org/',
	));
	Assert::same('http://nette.org/', $referrerRequest->getHeader('referer'));
	Assert::type('Nette\Http\Url', $referrerRequest->getReferrer());
	Assert::same('http://nette.org/', $referrerRequest->getReferrer()->getAbsoluteUrl());
});
