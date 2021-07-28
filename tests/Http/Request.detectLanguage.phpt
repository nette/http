<?php

/**
 * Test: Nette\Http\Request detectLanguage.
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$headers = ['Accept-Language' => 'en, cs'];
	$request = new Http\Request(new Http\UrlScript, headers: $headers);

	Assert::same('en', $request->detectLanguage(['en', 'cs']));
	Assert::same('en', $request->detectLanguage(['cs', 'en']));
	Assert::null($request->detectLanguage(['xx']));
});


test('', function () {
	$headers = ['Accept-Language' => 'da, en-gb;q=0.8, en;q=0.7'];
	$request = new Http\Request(new Http\UrlScript, headers: $headers);

	Assert::same('en-gb', $request->detectLanguage(['en', 'en-gb']));
	Assert::same('en', $request->detectLanguage(['en']));
});


test('', function () {
	$headers = [];
	$request = new Http\Request(new Http\UrlScript, headers: $headers);

	Assert::null($request->detectLanguage(['en']));
});


test('', function () {
	$headers = ['Accept-Language' => 'garbage'];
	$request = new Http\Request(new Http\UrlScript, headers: $headers);

	Assert::null($request->detectLanguage(['en']));
});
