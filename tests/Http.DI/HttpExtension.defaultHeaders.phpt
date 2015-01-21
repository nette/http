<?php

/**
 * Test: HttpExtension.
 */

use Nette\DI,
	Nette\Bridges\HttpDI\HttpExtension,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Headers are not testable in CLI mode');
}


$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);
eval($compiler->compile(array(), 'Container1'));

$container = new Container1;
$container->initialize();

$headers = headers_list();
Assert::contains( 'X-Frame-Options: SAMEORIGIN', $headers );
Assert::contains( 'Content-Type: text/html; charset=utf-8', $headers );
Assert::contains( 'X-Powered-By: Nette Framework', $headers );
