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
	featurePolicy:
		unsized-media: none
		geolocation:
			- self
			- https://example.com
		camera: *
EOD
, 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->initialize();

$headers = $container->getByType(Nette\Http\Response::class)->getHeaders();

Assert::same(["unsized-media 'none'; geolocation 'self' https://example.com; camera *;"], $headers['Feature-Policy']);
