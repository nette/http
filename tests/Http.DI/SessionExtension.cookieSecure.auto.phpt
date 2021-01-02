<?php

/**
 * Test: SessionExtension.
 */

declare(strict_types=1);

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\Bridges\HttpDI\SessionExtension;
use Nette\DI;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test('', function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('foo', new HttpExtension);
	$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

	$loader = new DI\Config\Loader;
	$config = $loader->load(Tester\FileMock::create('
	session:
		cookieSecure: auto

	services:
		foo.request: Nette\Http\Request(Nette\Http\UrlScript("http://www.nette.org"))
	', 'neon'));

	eval($compiler->addConfig($config)->setClassName('ContainerHttp')->compile());

	$container = new ContainerHttp;
	$container->getService('session')->start();
	$container->getService('session')->close();

	Assert::same(
		PHP_VERSION_ID >= 70300
			? ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true, 'samesite' => 'Lax']
			: ['lifetime' => 0, 'path' => '/; SameSite=Lax', 'domain' => '', 'secure' => false, 'httponly' => true],
		session_get_cookie_params()
	);
});


test('', function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('foo', new HttpExtension);
	$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

	$loader = new DI\Config\Loader;
	$config = $loader->load(Tester\FileMock::create('
	session:
		cookieSecure: auto

	services:
		foo.request: Nette\Http\Request(Nette\Http\UrlScript("https://www.nette.org"))
	', 'neon'));

	eval($compiler->addConfig($config)->setClassName('ContainerHttps')->compile());

	$container = new ContainerHttps;
	$container->getService('session')->start();
	$container->getService('session')->close();

	Assert::same(
		PHP_VERSION_ID >= 70300
			? ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']
			: ['lifetime' => 0, 'path' => '/; SameSite=Lax', 'domain' => '', 'secure' => true, 'httponly' => true],
		session_get_cookie_params()
	);
});
