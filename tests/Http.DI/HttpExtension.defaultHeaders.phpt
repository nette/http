<?php

/**
 * Test: HttpExtension.
 */

declare(strict_types=1);

use Nette\DI;
use Nette\Bridges\HttpDI\HttpExtension;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Headers are not testable in CLI mode');
}


$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);
eval($compiler->compile());

$container = new Container;
$container->initialize();

$headers = headers_list();
Assert::contains('X-Frame-Options: SAMEORIGIN', $headers);
Assert::contains('Content-Type: text/html; charset=utf-8', $headers);
Assert::contains('X-Powered-By: Nette Framework', $headers);
