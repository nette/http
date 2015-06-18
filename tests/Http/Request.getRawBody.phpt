<?php

/**
 * Test: Nette\Http\Request getRawBody.
 */

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$request = new Http\Request(new Http\UrlScript, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, function () {
		return 'raw body';
	});

	Assert::same('raw body', $request->getRawBody());
});


test(function () {
	$request = new Http\Request(new Http\UrlScript);

	Assert::null($request->getRawBody());
});
