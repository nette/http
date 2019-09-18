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
	headers:
		A: b
		C:
		D: 0
EOD
, 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->getService('http.response');

$headers = headers_list();
Assert::contains('X-Frame-Options: SAMEORIGIN', $headers);
Assert::contains('Content-Type: text/html; charset=utf-8', $headers);
Assert::contains('X-Powered-By: Nette Framework 3', $headers);
Assert::contains('A: b', $headers);
Assert::contains('D: 0', $headers);
Assert::notContains('C:', $headers);


echo ' '; @ob_flush(); flush();

Assert::true(headers_sent());

Assert::exception(function () use ($container) {
	$container->createService('http.response');
}, Nette\InvalidStateException::class, 'Cannot send header after %a%');
