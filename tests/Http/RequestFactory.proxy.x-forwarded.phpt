<?php

/**
 * Test: Nette\Http\RequestFactory and proxy with "X-forwarded" headers.
 */

declare(strict_types=1);

use Nette\Http\RequestFactory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_X_FORWARDED_FOR' => '23.75.45.200',
		'HTTP_X_FORWARDED_HOST' => 'otherhost',
	];

	$factory = new RequestFactory;
	$factory->setProxy('127.0.0.1');
	Assert::same('127.0.0.3', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('localhost', $factory->fromGlobals()->getRemoteHost());

	$factory->setProxy('127.0.0.1/8');
	Assert::same('23.75.45.200', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('a23-75-45-200.deploy.static.akamaitechnologies.com', $factory->fromGlobals()->getRemoteHost());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('otherhost', $url->getHost());
	Assert::same(80, $url->getPort());
});

test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_X_FORWARDED_FOR' => '23.75.45.200',
		'HTTP_X_FORWARDED_HOST' => 'otherhost:8080',
	];

	$factory = new RequestFactory;
	$factory->setProxy('127.0.0.3');
	Assert::same('23.75.45.200', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('a23-75-45-200.deploy.static.akamaitechnologies.com', $factory->fromGlobals()->getRemoteHost());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('otherhost', $url->getHost());
	Assert::same(8080, $url->getPort());
});

test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'HTTP_X_FORWARDED_FOR' => '23.75.45.200',
		'HTTP_X_FORWARDED_HOST' => 'otherhost',
		'HTTP_X_FORWARDED_PROTO' => 'https',
		'HTTP_X_FORWARDED_PORT' => '8080',
	];

	$factory = new RequestFactory;
	$factory->setProxy('127.0.0.3');

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('https', $url->getScheme());
	Assert::same('otherhost', $url->getHost());
	Assert::same(8080, $url->getPort());
});

test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.2', //proxy2
		'REMOTE_HOST' => 'proxy2',
		'HTTP_X_FORWARDED_FOR' => '123.123.123.123, not-ip.com, 172.16.0.1, 10.0.0.1',
		'HTTP_X_FORWARDED_HOST' => 'fake, not-ip.com, real, proxy1',
	];

	$factory = new RequestFactory;
	$factory->setProxy('10.0.0.0/24');
	Assert::same('172.16.0.1', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('172.16.0.1', $factory->fromGlobals()->getRemoteHost());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('real', $url->getHost());
	Assert::same(80, $url->getPort());

	$factory->setProxy(['10.0.0.1', '10.0.0.2']);
	Assert::same('172.16.0.1', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('172.16.0.1', $factory->fromGlobals()->getRemoteHost());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('real', $url->getHost());
	Assert::same(80, $url->getPort());
});

test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.2', //proxy2
		'REMOTE_HOST' => 'proxy2',
		'HTTP_X_FORWARDED_FOR' => '123.123.123.123, not-ip.com, 172.16.0.1, 10.0.0.1',
		'HTTP_X_FORWARDED_HOST' => 'fake, not-ip.com, real:8080, proxy1',
	];

	$factory = new RequestFactory;
	$factory->setProxy(['10.0.0.1', '10.0.0.2']);
	Assert::same('172.16.0.1', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('172.16.0.1', $factory->fromGlobals()->getRemoteHost());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('real', $url->getHost());
	Assert::same(8080, $url->getPort());
});
