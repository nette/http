<?php

declare(strict_types=1);

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\Bridges\HttpDI\SessionExtension;
use Nette\DI;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('http', new HttpExtension);
	$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

	$loader = new DI\Config\Loader;
	$config = $loader->load(Tester\FileMock::create('
	http:
		cookieSecure: no
	', 'neon'));

	eval($compiler->addConfig($config)->setClassName('ContainerNo')->compile());

	$container = new ContainerNo;

	Assert::false($container->getService('http.response')->cookieSecure);
	Assert::false($container->getService('session.session')->getOptions()['cookie_secure']);
});


test('', function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('http', new HttpExtension);
	$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

	$loader = new DI\Config\Loader;
	$config = $loader->load(Tester\FileMock::create('
	http:
		cookieSecure: yes
	', 'neon'));

	eval($compiler->addConfig($config)->setClassName('ContainerYes')->compile());

	$container = new ContainerYes;

	Assert::true($container->getService('http.response')->cookieSecure);
	Assert::true($container->getService('session.session')->getOptions()['cookie_secure']);
});


test('', function () {
	$compiler = new DI\Compiler;
	$compiler->addExtension('http', new HttpExtension);
	$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

	$loader = new DI\Config\Loader;
	$config = $loader->load(Tester\FileMock::create('
	http:
		cookieSecure: auto
	', 'neon'));

	eval($compiler->addConfig($config)->setClassName('ContainerAuto')->compile());

	$container = new ContainerAuto;
	$container->addService('http.request', new Nette\Http\Request(new Nette\Http\UrlScript('http://localhost')));

	Assert::false($container->getService('http.response')->cookieSecure);
	Assert::false($container->getService('session.session')->getOptions()['cookie_secure']);

	$container = new ContainerAuto;
	$container->addService('http.request', new Nette\Http\Request(new Nette\Http\UrlScript('https://localhost')));

	Assert::true($container->getService('http.response')->cookieSecure);
	Assert::true($container->getService('session.session')->getOptions()['cookie_secure']);
});
