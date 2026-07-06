<?php declare(strict_types=1);

/**
 * Test: Nette\Http\RequestFactory trusted forwarding header selection.
 */

use Nette\Http\RequestFactory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('forwarded: false ignores the Forwarded header, falls back to X-Forwarded-For', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.1',
		'HTTP_FORWARDED' => 'for=6.6.6.6',
		'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
	];

	$factory = new RequestFactory;
	$factory->setProxy('10.0.0.1', forwarded: false);
	Assert::same('1.2.3.4', $factory->fromGlobals()->getRemoteAddress());
});


test('xForwarded: false ignores X-Forwarded-For', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.1',
		'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
	];

	$factory = new RequestFactory;
	$factory->setProxy('10.0.0.1', xForwarded: false);
	// no trusted header to read, the proxy address is kept
	Assert::same('10.0.0.1', $factory->fromGlobals()->getRemoteAddress());
});


test('both disabled trusts no forwarding header', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.1',
		'HTTP_FORWARDED' => 'for=6.6.6.6',
		'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
	];

	$factory = new RequestFactory;
	$factory->setProxy('10.0.0.1', forwarded: false, xForwarded: false);
	Assert::same('10.0.0.1', $factory->fromGlobals()->getRemoteAddress());
});


test('default trusts both, Forwarded takes precedence', function () {
	$_SERVER = [
		'REMOTE_ADDR' => '10.0.0.1',
		'HTTP_FORWARDED' => 'for=1.2.3.4',
		'HTTP_X_FORWARDED_FOR' => '5.6.7.8',
	];

	$factory = new RequestFactory;
	$factory->setProxy('10.0.0.1');
	Assert::same('1.2.3.4', $factory->fromGlobals()->getRemoteAddress());
});
