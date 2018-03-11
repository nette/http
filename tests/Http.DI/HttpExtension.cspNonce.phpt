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
		default-src: "'none'"
		script-src: "'nonce'"
	csp-report:
		default-src: "'nonce'"
		report-uri: https://example.com/report
EOD
	, 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->initialize();

$headers = headers_list();
$joinedHeaders = implode("\n", $headers);

//Assert::contains("Content-Security-Policy: default-src 'none';", $headers);
Assert::match("#Content-Security-Policy: default-src 'none'; script-src 'nonce-[A-Za-z0-9=\/\+]+';#", $joinedHeaders);
Assert::match("#Content-Security-Policy-Report-Only: default-src 'nonce-[A-Za-z0-9=\/\+]+'; report-uri https:\/\/example\.com\/report;#", $joinedHeaders);

preg_match('~(nonce-[A-Za-z0-9=\/\+]+)~', $joinedHeaders, $matches);
Assert::equal(2, substr_count($joinedHeaders, $matches[1]), 'Nonces do not match.');


echo ' '; @ob_flush(); flush();

Assert::true(headers_sent());

Assert::exception(function () use ($container) {
	$container->initialize();
}, Nette\InvalidStateException::class, 'Cannot send header after %a%');
