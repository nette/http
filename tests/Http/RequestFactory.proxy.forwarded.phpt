<?php declare(strict_types=1);

/**
 * Test: Nette\Http\RequestFactory and proxy with "Forwarded" header.
 */

use Nette\Http\RequestFactory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

test('forwarded header handling with proxy', function () {
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

	$url = $factory->fromGlobals()->getUrl();
	Assert::same('http', $url->getScheme());
	Assert::same('192.168.0.1', $url->getHost());
});

test('forwarded header with port numbers', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '127.0.0.3',
		'REMOTE_HOST' => 'localhost',
		'HTTP_FORWARDED' => 'for=23.75.45.200:8080;host=192.168.0.1:8080',
	];

	$factory = new RequestFactory;

	$factory->setProxy('127.0.0.3');
	Assert::same('23.75.45.200', $factory->fromGlobals()->getRemoteAddress());

	$url = $factory->fromGlobals()->getUrl();
	Assert::same(8080, $url->getPort());
	Assert::same('192.168.0.1', $url->getHost());
});


test('IPv6 addresses in Forwarded header', function () {
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

test('IPv6 addresses and ports in Forwarded header', function () {
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


test('forwarded protocol (HTTPS) handling', function () {
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


test('trusted proxies are stripped from the Forwarded chain (rightmost untrusted hop wins)', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.2',
		'HTTP_FORWARDED' => 'for=23.75.45.200, for=172.16.0.1, for=10.0.0.1',
	];

	$factory = new RequestFactory;
	$factory->setProxy('10.0.0.0/24');
	Assert::same('172.16.0.1', $factory->fromGlobals()->getRemoteAddress());
});


test('host and proto are resolved from the client hop, not from an injected element', function () {
	// the leftmost element is the one a client can forge by appending it
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.2',
		'HTTP_FORWARDED' => 'for=6.6.6.6;host=evil.test;proto=http, for=172.16.0.1;host=real.test;proto=https, for=10.0.0.1',
	];

	$factory = new RequestFactory;
	$factory->setProxy('10.0.0.0/24');
	$request = $factory->fromGlobals();
	Assert::same('172.16.0.1', $request->getRemoteAddress());
	Assert::same('real.test', $request->getUrl()->getHost());
	Assert::same('https', $request->getUrl()->getScheme());
});


test('obfuscated or invalid client identifier yields a null remote address', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.1',
		'HTTP_FORWARDED' => 'for=unknown',
	];

	$factory = new RequestFactory;
	$factory->setProxy('10.0.0.1');
	Assert::null($factory->fromGlobals()->getRemoteAddress());
});
