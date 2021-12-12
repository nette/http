<?php

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
	disableNetteCookie: yes
EOD
, 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->initialize();

$headers = headers_list();
Assert::notContains('Set-Cookie: _nss=1', implode('', $headers));
