<?php

/**
 * Test: SessionExtension.
 */

use Nette\DI;
use Nette\Bridges\HttpDI\HttpExtension;
use Nette\Bridges\HttpDI\SessionExtension;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->addExtension('foo', new HttpExtension);
$compiler->addExtension('session', new SessionExtension);

$loader = new DI\Config\Loader;
$config = $loader->load(Tester\FileMock::create('
session:
	cookiePath: /x
	cookieDomain: abc
	cookieSecure: yes
', 'neon'));

eval($compiler->compile($config, 'Container1'));

$container = new Container1;
$container->getService('session')->start();

Assert::same(
	['lifetime' => 0, 'path' => '/x', 'domain' => 'abc', 'secure' => TRUE, 'httponly' => TRUE],
	session_get_cookie_params()
);
