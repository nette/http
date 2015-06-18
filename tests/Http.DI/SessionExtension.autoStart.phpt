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
	autoStart: yes
', 'neon'));

eval($compiler->compile($config, 'Container1'));

$container = new Container1;

$session = $container->getService('session.session');
Assert::type('Nette\Http\Session', $session);
Assert::false($session->isStarted());

// aliases
Assert::same($session, $container->getService('session'));


$container->initialize();
Assert::same(PHP_SAPI !== 'cli', $session->isStarted());
