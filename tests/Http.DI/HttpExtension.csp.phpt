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
	csp:
		default-src: "'self' https://example.com"
		upgrade-insecure-requests:
		script-src: 'nonce'
		style-src:
			- self
			- https://example.com
			- http:
		require-sri-for: style
		sandbox: allow-forms
		plugin-types: application/x-java-applet

	cspReportOnly:
		default-src: "'nonce'"
		report-uri: https://example.com/report
		upgrade-insecure-requests: true
		block-all-mixed-content: false
EOD
, 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->initialize();

$headers = $container->getByType(Nette\Http\Response::class)->getHeaders();

preg_match('#nonce-([\w+/]+=*)#', implode($headers['Content-Security-Policy']), $nonce);
Assert::same(["default-src 'self' https://example.com; upgrade-insecure-requests; script-src 'nonce-$nonce[1]'; style-src 'self' https://example.com http:; require-sri-for style; sandbox allow-forms; plugin-types application/x-java-applet;"], $headers['Content-Security-Policy']);
Assert::same(["default-src 'nonce-$nonce[1]'; report-uri https://example.com/report; upgrade-insecure-requests;"], $headers['Content-Security-Policy-Report-Only']);
