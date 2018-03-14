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
	csp:
		default-src: "'self' https://example.com"
		upgrade-insecure-requests:
		script-src: 'nonce'
		style-src:
			- self
			- https://example.com
EOD
, 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->initialize();

$headers = headers_list();

preg_match('#nonce-([\w+/]+=*)#', implode($headers), $nonce);
Assert::contains("Content-Security-Policy: default-src 'self' https://example.com; upgrade-insecure-requests; script-src 'nonce-$nonce[1]'; style-src 'self' https://example.com;", $headers);


echo ' '; @ob_flush(); flush();

Assert::true(headers_sent());

Assert::exception(function () use ($container) {
	$container->initialize();
}, Nette\InvalidStateException::class, 'Cannot send header after %a%');
