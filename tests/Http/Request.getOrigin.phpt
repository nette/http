<?php

declare(strict_types=1);

use Nette\Http;
use Nette\Http\UrlImmutable;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('no Origin header', function () {
	$request = new Http\Request(new Http\UrlScript);
	Assert::null($request->getOrigin());
});


test('origin header \'null\'', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Origin' => 'null',
	]);
	Assert::null($request->getOrigin());
});


test('valid Origin header', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Origin' => 'https://nette.org',
	]);
	Assert::equal(new UrlImmutable('https://nette.org'), $request->getOrigin());
});
