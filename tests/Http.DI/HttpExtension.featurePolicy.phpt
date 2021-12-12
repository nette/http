<?php

/**
 * Test: HttpExtension.
 */

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

$headers = headers_list();

Assert::contains("Feature-Policy: unsized-media 'none'; geolocation 'self' https://example.com; camera *;", $headers);


// flush buffers
echo str_repeat(' ', ini_get('output_buffering') + 1);

Assert::true(headers_sent());

Assert::exception(function () use ($container) {
	$container->initialize();
}, Nette\InvalidStateException::class, 'Cannot send header after %a%');
