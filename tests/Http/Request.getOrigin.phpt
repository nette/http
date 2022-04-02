<?php

declare(strict_types=1);

use Nette\Http;
use Nette\Http\UrlImmutable;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('missing origin', function () {
	$_SERVER = [];
	$factory = new Http\RequestFactory;
	$request = $factory->fromGlobals();

	Assert::null($request->getOrigin());
});


test('opaque origin', function () {
	$_SERVER = [
		'HTTP_ORIGIN' => 'null',
	];
	$factory = new Http\RequestFactory;
	$request = $factory->fromGlobals();

	Assert::null($request->getOrigin());
});


test('normal origin', function () {
	$_SERVER = [
		'HTTP_ORIGIN' => 'https://nette.org',
	];
	$factory = new Http\RequestFactory;
	$request = $factory->fromGlobals();

	Assert::equal(new UrlImmutable('https://nette.org'), $request->getOrigin());
});
