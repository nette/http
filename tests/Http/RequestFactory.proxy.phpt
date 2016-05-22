<?php

/**
 * Test: Nette\Http\RequestFactory and proxy.
 */

use Nette\Http\RequestFactory;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_X_FORWARDED_FOR' => '23.75.45.200',
		'HTTP_X_FORWARDED_HOST' => 'otherhost',
	];

	$factory = new RequestFactory;
	$factory->setProxy('127.0.0.1');
	Assert::same('127.0.0.3', $factory->createHttpRequest()->getRemoteAddress());
	Assert::same('localhost', $factory->createHttpRequest()->getRemoteHost());

	$factory->setProxy('127.0.0.1/8');
	Assert::same('23.75.45.200', $factory->createHttpRequest()->getRemoteAddress());
	Assert::same('otherhost', $factory->createHttpRequest()->getRemoteHost());
});

test(function () {
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.2', //proxy2
		'REMOTE_HOST' => 'proxy2',
		'HTTP_X_FORWARDED_FOR' => '123.123.123.123, 172.16.0.1, 10.0.0.1',
		'HTTP_X_FORWARDED_HOST' => 'fake, real, proxy1',
	];

	$factory = new RequestFactory;
	$factory->setProxy('10.0.0.0/24');
	Assert::same('172.16.0.1', $factory->createHttpRequest()->getRemoteAddress());
	Assert::same('real', $factory->createHttpRequest()->getRemoteHost());

	$factory->setProxy(['10.0.0.1', '10.0.0.2']);
	Assert::same('172.16.0.1', $factory->createHttpRequest()->getRemoteAddress());
	Assert::same('real', $factory->createHttpRequest()->getRemoteHost());
});

test(function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_FORWARDED' => 'For=23.75.45.200:85;HOST=otherhost',
	];

	$factory = new RequestFactory;
	$factory->setProxy('127.0.0.1');
	Assert::same('127.0.0.3', $factory->createHttpRequest()->getRemoteAddress());
	Assert::same('localhost', $factory->createHttpRequest()->getRemoteHost());

	$factory->setProxy('127.0.0.1/8');
	Assert::same('23.75.45.200', $factory->createHttpRequest()->getRemoteAddress());
	Assert::same('otherhost', $factory->createHttpRequest()->getRemoteHost());
});


test(function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_FORWARDED' => 'for="[2001:db8:cafe::17]:47011";host=192.168.0.1:8080',
	];

	$factory = new RequestFactory;

	$factory->setProxy('127.0.0.3');
	Assert::same('2001:db8:cafe::17', $factory->createHttpRequest()->getRemoteAddress());
	Assert::same('192.168.0.1', $factory->createHttpRequest()->getRemoteHost());

	$url = $factory->createHttpRequest()->getUrl();
	Assert::same(8080, $url->getPort());
});

test(function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_FORWARDED' => 'for="[2001:db8:cafe::17]";host=192.168.0.1;scheme=https',
	];

	$factory = new RequestFactory;

	$factory->setProxy('127.0.0.3');
	Assert::same('2001:db8:cafe::17', $factory->createHttpRequest()->getRemoteAddress());

	$url = $factory->createHttpRequest()->getUrl();
	Assert::same('https', $url->getScheme());
});
