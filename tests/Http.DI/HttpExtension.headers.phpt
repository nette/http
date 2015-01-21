<?php

/**
 * Test: HttpExtension.
 */

use Nette\DI,
	Nette\Bridges\HttpDI\HttpExtension,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Headers are not testable in CLI mode');
}


$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);
$loader = new DI\Config\Loader;
$config = $loader->load(Tester\FileMock::create('
http:
	headers:
		A: b
		C:
', 'neon'));

eval($compiler->compile($config, 'Container1'));

$container = new Container1;
$container->initialize();

$headers = headers_list();
Assert::contains( 'X-Frame-Options: SAMEORIGIN', $headers );
Assert::contains( 'Content-Type: text/html; charset=utf-8', $headers );
Assert::contains( 'X-Powered-By: Nette Framework', $headers );
Assert::contains( 'A: b', $headers );
Assert::notContains( 'C:', $headers );



echo ' '; @ob_flush(); flush();

Assert::true( headers_sent() );

Assert::error(function() use ($container) {
	$container->initialize();
}, array(
	array(E_WARNING, 'Cannot modify header information - headers already sent %a%'),
	array(E_WARNING, 'Cannot modify header information - headers already sent %a%'),
	array(E_WARNING, 'Cannot modify header information - headers already sent %a%'),
	array(E_WARNING, 'Cannot modify header information - headers already sent %a%'),
));
