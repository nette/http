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


$compiler = new DI\Compiler;
$compiler->addExtension('foo', new HttpExtension);
$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

$loader = new DI\Config\Loader;
$config = $loader->load(Tester\FileMock::create('
foo:
	cookieSecure: yes

session:
	cookiePath: /x
	cookieDomain: domain
	cookieSamesite: Lax
	readAndClose: yes

services:
	foo.request: Nette\Http\Request(Nette\Http\UrlScript("http://www.nette.org"))
', 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->getService('session')->start();

Assert::same(
	PHP_VERSION_ID >= 70300
		? ['lifetime' => 0, 'path' => '/x', 'domain' => 'nette.org', 'secure' => true, 'httponly' => true, 'samesite' => 'Lax']
		: ['lifetime' => 0, 'path' => '/x; SameSite=Lax', 'domain' => 'nette.org', 'secure' => true, 'httponly' => true],
	session_get_cookie_params()
);

// readAndClose
Assert::same(PHP_SESSION_NONE, session_status());
