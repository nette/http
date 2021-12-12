<?php

declare(strict_types=1);

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\Bridges\HttpDI\SessionExtension;
use Nette\DI;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('cookiePath & cookieDomain', function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('http', new HttpExtension);
	$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

	$loader = new DI\Config\Loader;
	$config = $loader->load(Tester\FileMock::create('
	http:
		cookiePath: /x
		cookieDomain: www.nette.org
	', 'neon'));

	eval($compiler->addConfig($config)->setClassName('ContainerA')->compile());

	$container = new ContainerA;
	Assert::same('/x', $container->getService('http.response')->cookiePath);
	Assert::same('www.nette.org', $container->getService('http.response')->cookieDomain);
});


test('cookieDomain = domain', function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('http', new HttpExtension);
	$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

	$loader = new DI\Config\Loader;
	$config = $loader->load(Tester\FileMock::create('
	http:
		cookieDomain: domain

	services:
		http.request: Nette\Http\Request(Nette\Http\UrlScript("http://www.nette.org"))
	', 'neon'));

	eval($compiler->addConfig($config)->setClassName('ContainerB')->compile());

	$container = new ContainerB;
	Assert::same('nette.org', $container->getService('http.response')->cookieDomain);
});
