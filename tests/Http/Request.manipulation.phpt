<?php

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$url = new Http\UrlScript('http://nette.org/?arg=hello');
	$request = new Http\Request($url);

	Assert::same($url, $request->getUrl());
	Assert::same('hello', $request->getQuery('arg'));

	$url2 = new Http\UrlScript('http://nette.org/?arg=another');
	$request = $request->withUrl($url2);

	Assert::same($url2, $request->getUrl());
	Assert::same('another', $request->getQuery('arg'));
});
