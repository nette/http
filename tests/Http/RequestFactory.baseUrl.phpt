<?php declare(strict_types=1);

/**
 * Test: Nette\Http\RequestFactory and base URL fallback.
 */

use Nette\Http\RequestFactory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('base URL is used when no host is available', function () {
	$_SERVER = [
		'SCRIPT_NAME' => 'cron.php',
	];
	$factory = (new RequestFactory)->setBaseUrl('https://example.com/sub');
	$url = $factory->fromGlobals()->getUrl();
	Assert::same('https://example.com/sub/', (string) $url);
	Assert::same('/sub/', $url->getScriptPath());
	Assert::same('/sub/', $url->getBasePath());
	Assert::same('https://example.com/sub/', $url->getBaseUrl());
});


test('port and trailing slash', function () {
	$_SERVER = [];
	$factory = (new RequestFactory)->setBaseUrl('http://localhost:8080/');
	Assert::same('http://localhost:8080/', (string) $factory->fromGlobals()->getUrl());

	$factory = (new RequestFactory)->setBaseUrl('http://localhost');
	Assert::same('http://localhost/', (string) $factory->fromGlobals()->getUrl());
});


test('host from the environment takes precedence', function () {
	$_SERVER = [
		'HTTP_HOST' => 'nette.org',
		'REQUEST_URI' => '/index.php?a=1',
		'SCRIPT_NAME' => '/index.php',
	];
	$factory = (new RequestFactory)->setBaseUrl('https://example.com/sub');
	$url = $factory->fromGlobals()->getUrl();
	Assert::same('http://nette.org/index.php?a=1', (string) $url);
	Assert::same('/index.php', $url->getScriptPath());
});


test('base URL must be absolute', function () {
	Assert::exception(
		fn() => (new RequestFactory)->setBaseUrl('example.com/sub'),
		Nette\InvalidArgumentException::class,
		"Base URL 'example.com/sub' must be absolute.",
	);
});


test('forceHttps applies to the base URL too', function () {
	$_SERVER = [];
	$factory = (new RequestFactory)->setBaseUrl('http://example.com')->setForceHttps();
	Assert::same('https://example.com/', (string) $factory->fromGlobals()->getUrl());
});


test('without base URL the host stays empty', function () {
	$_SERVER = [];
	$factory = new RequestFactory;
	Assert::same('', $factory->fromGlobals()->getUrl()->getHost());
});
