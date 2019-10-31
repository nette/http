<?php declare(strict_types=1);

/**
 * Test: HttpExtension.
 */

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);
eval($compiler->compile());

$container = new Container;
$container->initialize();

$headers = $container->getByType(Nette\Http\Response::class)->getHeaders();
Assert::same([
	'X-Powered-By' => ['Nette Framework 3'],
	'Content-Type' => ['text/html; charset=utf-8'],
	'X-Frame-Options' => ['SAMEORIGIN'],
], $headers);
