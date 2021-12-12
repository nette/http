<?php

declare(strict_types=1);

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Headers are not testable in CLI mode');
}


$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);

// protection is enabled by default
eval($compiler->compile());

$container = new Container;
$container->initialize();

$headers = headers_list();
Assert::contains(
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: _nss=1; path=/; HttpOnly; SameSite=Strict'
		: 'Set-Cookie: _nss=1; path=/; SameSite=Strict; HttpOnly',
	$headers
);
