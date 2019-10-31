<?php

/**
 * Test: Nette\Http\ResponseEmitter.
 * @httpCode 123
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$response = new Http\Response;
	$response->setCode(123, 'my reason');
	$response->setHeader('A', 'b');
	$response->addHeader('A', 'c');
	$response->setBody('hello');

	$emitter = new Http\ResponseEmitter;

	ob_start();
	$emitter->send($response);
	Assert::same('hello', ob_get_clean());


	if (PHP_SAPI !== 'cli') {
		Assert::same(['A: b', 'A: c'], headers_list());
	}
});


test(function () {
	$response = new Http\Response;
	$response->setCode(123, 'my reason');
	$response->setHeader('B', 'b');
	$response->setBody(function () {
		echo 'nette';
	});

	$emitter = new Http\ResponseEmitter;

	ob_start();
	$emitter->send($response);
	Assert::same('nette', ob_get_clean());


	if (PHP_SAPI !== 'cli') {
		Assert::same(['B: b'], headers_list());
	}
});
