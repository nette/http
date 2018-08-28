<?php

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\Bridges\HttpDI\SessionExtension;
use Nette\DI;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Headers are not testable in CLI mode');
}


$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);
$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

$loader = new DI\Config\Loader;
$config = $loader->load(Tester\FileMock::create('
http:
	sameSiteProtection: yes
', 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->initialize();

$headers = headers_list();
$headers = str_replace('httponly', 'HttpOnly', $headers);
Assert::contains(
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: nette-samesite=1; path=/; HttpOnly; SameSite=Strict'
		: 'Set-Cookie: nette-samesite=1; path=/; SameSite=Strict; HttpOnly',
	$headers
);
Assert::same('Lax', $container->getService('session.session')->getOptions()['cookie_samesite']);
