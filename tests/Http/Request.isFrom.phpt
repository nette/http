<?php

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('matches both headers', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Sec-Fetch-Site' => 'same-origin',
		'Sec-Fetch-Dest' => 'document',
	]);

	Assert::true($request->isFrom('same-origin', 'document'));
});


test('fails when expected header missing', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Sec-Fetch-Site' => 'same-origin',
	]);

	Assert::false($request->isFrom('same-origin', 'document'));
});


test('accepts multiple expected values', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Sec-Fetch-Site' => 'cross-site',
		'Sec-Fetch-Dest' => 'image',
	]);

	Assert::true($request->isFrom(['same-origin', 'cross-site'], ['document', 'image']));
	Assert::false($request->isFrom(['cross-site'], ['Document']));
	Assert::false($request->isFrom(['Cross-Site'], ['image']));
});


test('fallback same-origin from Origin header', function () {
	$url = new Http\UrlScript('https://nette.org/app/');
	$request = new Http\Request($url, headers: [
		'Origin' => 'https://nette.org',
	]);

	Assert::true($request->isFrom('same-origin'));
});


test('fallback cross-site from Origin header', function () {
	$url = new Http\UrlScript('https://nette.org/');
	$request = new Http\Request($url, headers: [
		'Origin' => 'https://example.com',
	]);

	Assert::true($request->isFrom('cross-site'));
});


test('fallback missing without Origin header', function () {
	$url = new Http\UrlScript('https://nette.org/');
	$request = new Http\Request($url);

	Assert::false($request->isFrom('same-origin'));
});


test('fallback not used when header present', function () {
	$url = new Http\UrlScript('https://nette.org/');
	$request = new Http\Request($url, headers: [
		'Sec-Fetch-Site' => 'none',
		'Origin' => 'https://nette.org',
	]);

	Assert::false($request->isFrom('same-origin'));
});


test('fallback cross-site when port differs', function () {
	$url = new Http\UrlScript('https://nette.org:443');
	$request = new Http\Request($url, headers: [
		'Origin' => 'https://nette.org:444',
	]);

	Assert::true($request->isFrom('cross-site'));
});


test('fallback ignored for invalid Origin', function () {
	$url = new Http\UrlScript('https://nette.org/');
	$request = new Http\Request($url, headers: [
		'Origin' => 'null',
	]);

	Assert::false($request->isFrom('same-origin'));
});
