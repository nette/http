<?php

declare(strict_types=1);

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);

// protection is enabled by default
eval($compiler->compile());

$container = new Container;
$container->initialize();

$headers = $container->getByType(Nette\Http\Response::class)->getHeaders();
Assert::same(
	['nette-samesite=1; path=/; HttpOnly; SameSite=Strict'],
	$headers['Set-Cookie']
);
