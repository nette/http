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
session:
	autoStart: yes
', 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;

$session = $container->getService('session.session');
Assert::type(Nette\Http\Session::class, $session);
Assert::false($session->isStarted());

// aliases
Assert::same($session, $container->getService('session'));


$container->initialize();
Assert::same(PHP_SAPI !== 'cli', $session->isStarted());
