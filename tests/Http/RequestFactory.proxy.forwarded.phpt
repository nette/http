<?php

/**
 * Test: Nette\Http\RequestFactory and proxy with "Forwarded" header.
 */

declare(strict_types=1);

use Nette\Http\RequestFactory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_FORWARDED' => 'for=23.75.45.200;host=192.168.0.1',
	];

	$factory = new RequestFactory;
	$factory->setProxy('127.0.0.1');
	Assert::same('127.0.0.3', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('localhost', $factory->fromGlobals()->getRemoteHost());

	$factory->setProxy('127.0.0.1/8');
	Assert::same('23.75.45.200', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('a23-75-45-200.deploy.static.akamaitechnologies.com', $factory->fromGlobals()->getRemoteHost());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('http', $url->getScheme());
	Assert::same('192.168.0.1', $url->getHost());
});

test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_FORWARDED' => 'for=23.75.45.200:8080;host=192.168.0.1:8080',
	];

	$factory = new RequestFactory;

	$factory->setProxy('127.0.0.3');
	Assert::same('23.75.45.200', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('a23-75-45-200.deploy.static.akamaitechnologies.com', $factory->fromGlobals()->getRemoteHost());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same(8080, $url->getPort());
	Assert::same('192.168.0.1', $url->getHost());
});


test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_FORWARDED' => 'for="[2001:db8:cafe::17]";host="[2001:db8:cafe::18]"',
	];

	$factory = new RequestFactory;

	$factory->setProxy('127.0.0.3');
	Assert::same('2001:db8:cafe::17', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('2001:db8:cafe::17', $factory->fromGlobals()->getRemoteHost());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('[2001:db8:cafe::18]', $url->getHost());
});

test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_FORWARDED' => 'for="[2001:db8:cafe::17]:47831";host="[2001:db8:cafe::18]:47832"',
	];

	$factory = new RequestFactory;

	$factory->setProxy('127.0.0.3');
	Assert::same('2001:db8:cafe::17', $factory->fromGlobals()->getRemoteAddress());
	Assert::same('2001:db8:cafe::17', $factory->fromGlobals()->getRemoteHost());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same(47832, $url->getPort());
	Assert::same('[2001:db8:cafe::18]', $url->getHost());
});


test('', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_FORWARDED' => 'for="[2001:db8:cafe::17]:47831" ; host="[2001:db8:cafe::18]:47832" ; proto=https',
	];

	$factory = new RequestFactory;
	$factory->setProxy('127.0.0.3');

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('https', $url->getScheme());
});
