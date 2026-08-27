<?php declare(strict_types=1);

/**
 * Test: HttpExtension baseUrl configuration.
 */

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\DI;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$_SERVER = [];

$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);
$compiler->addConfig(['http' => ['baseUrl' => 'https://example.com/sub']]);
eval($compiler->compile());

$container = new Container;
Assert::same('https://example.com/sub/', (string) $container->getService('http.request')->getUrl());
