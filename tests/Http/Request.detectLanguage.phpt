<?php declare(strict_types=1);

/**
 * Test: Nette\Http\Request detectLanguage.
 */

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('basic language preference', function () {
	$headers = ['Accept-Language' => 'en, cs'];
	$request = new Http\Request(new Http\UrlScript, headers: $headers);

	Assert::same('en', $request->detectLanguage(['en', 'cs']));
	Assert::same('en', $request->detectLanguage(['cs', 'en']));
	Assert::null($request->detectLanguage(['xx']));
});


test('language with quality weights', function () {
	$headers = ['Accept-Language' => 'da, en-gb;q=0.8, en;q=0.7'];
	$request = new Http\Request(new Http\UrlScript, headers: $headers);

	Assert::same('en-gb', $request->detectLanguage(['en', 'en-gb']));
	Assert::same('en', $request->detectLanguage(['en']));
});


test('case-insensitive matching, original case is returned', function () {
	$headers = ['Accept-Language' => 'cs_CZ, EN;q=0.8'];
	$request = new Http\Request(new Http\UrlScript, headers: $headers);

	Assert::same('CS', $request->detectLanguage(['CS', 'En']));
	Assert::same('cs-cz', $request->detectLanguage(['en', 'cs-cz']));
});


test('wildcard matches any supported language', function () {
	$request = new Http\Request(new Http\UrlScript, headers: ['Accept-Language' => 'fr, *;q=0.1']);

	Assert::same('en', $request->detectLanguage(['en', 'cs'])); // fr unsupported, * falls back
	Assert::null($request->detectLanguage([])); // nothing to fall back to
});


test('no Accept-Language header', function () {
	$headers = [];
	$request = new Http\Request(new Http\UrlScript, headers: $headers);

	Assert::null($request->detectLanguage(['en']));
});


test('invalid Accept-Language header', function () {
	$headers = ['Accept-Language' => 'garbage'];
	$request = new Http\Request(new Http\UrlScript, headers: $headers);

	Assert::null($request->detectLanguage(['en']));
});
