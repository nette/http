<?php

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$factory = new Nette\Http\RequestFactory;
	$response = new Nette\Http\Response;
	$session = new Nette\Http\Session($factory->fromGlobals(), $response);

	$session->setOptions([
		'cache_limiter' => 'public',
		'cache_expire' => '180',
	]);
	$session->start();

	Assert::same(
		[
			'Set-Cookie' => ['PHPSESSID=' . $session->getId() . '; path=/; HttpOnly'],
			'Expires' => [Nette\Http\Helpers::formatDate(time() + 180 * 60)],
			'Cache-Control' => ['public, max-age=10800'],
			'Last-Modified' => [Nette\Http\Helpers::formatDate(getlastmod())],
		],
		$response->getHeaders()
	);

	$session->close();
});


test(function () {
	$factory = new Nette\Http\RequestFactory;
	$response = new Nette\Http\Response;
	$session = new Nette\Http\Session($factory->fromGlobals(), $response);

	$session->setOptions([
		'cache_limiter' => 'private_no_expire',
		'cache_expire' => '180',
	]);
	$session->start();

	Assert::same(
		[
			'Set-Cookie' => ['PHPSESSID=' . $session->getId() . '; path=/; HttpOnly'],
			'Cache-Control' => ['private, max-age=10800'],
			'Last-Modified' => [Nette\Http\Helpers::formatDate(getlastmod())],
		],
		$response->getHeaders()
	);

	$session->close();
});


test(function () {
	$factory = new Nette\Http\RequestFactory;
	$response = new Nette\Http\Response;
	$session = new Nette\Http\Session($factory->fromGlobals(), $response);

	$session->setOptions([
		'cache_limiter' => 'private',
		'cache_expire' => '180',
	]);
	$session->start();

	Assert::same(
		[
			'Set-Cookie' => ['PHPSESSID=' . $session->getId() . '; path=/; HttpOnly'],
			'Expires' => ['Mon, 23 Jan 1978 10:00:00 GMT'],
			'Cache-Control' => ['private, max-age=10800'],
			'Last-Modified' => [Nette\Http\Helpers::formatDate(getlastmod())],
		],
		$response->getHeaders()
	);

	$session->close();
});


test(function () {
	$factory = new Nette\Http\RequestFactory;
	$response = new Nette\Http\Response;
	$session = new Nette\Http\Session($factory->fromGlobals(), $response);

	$session->setOptions([
		'cache_limiter' => 'nocache',
	]);
	$session->start();

	Assert::same(
		[
			'Set-Cookie' => ['PHPSESSID=' . $session->getId() . '; path=/; HttpOnly'],
			'Expires' => ['Mon, 23 Jan 1978 10:00:00 GMT'],
			'Cache-Control' => ['no-store, no-cache, must-revalidate'],
			'Pragma' => ['no-cache'],
		],
		$response->getHeaders()
	);

	$session->close();
});
