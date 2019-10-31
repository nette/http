<?php

/**
 * Test: HttpExtension.
 */

declare(strict_types=1);

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);
$loader = new DI\Config\Loader;
$config = $loader->load(Tester\FileMock::create(<<<'EOD'
http:
	headers:
		A: b
		C:
		D: 0
EOD
, 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->initialize();

$headers = $container->getByType(Nette\Http\Response::class)->getHeaders();
Assert::same(['SAMEORIGIN'], $headers['X-Frame-Options']);
Assert::same(['text/html; charset=utf-8'], $headers['Content-Type']);
Assert::same(['Nette Framework 3'], $headers['X-Powered-By']);
Assert::same(['b'], $headers['A']);
Assert::same(['0'], $headers['D']);
Assert::false(isset($headers['C']));
