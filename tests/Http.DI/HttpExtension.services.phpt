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
eval($compiler->compile());

$container = new Container;

Assert::type(Nette\Http\RequestFactory::class, $container->getService('http.requestFactory'));
Assert::type(Nette\Http\Request::class, $container->getService('http.request'));
Assert::type(Nette\Http\Response::class, $container->getService('http.response'));

// aliases
Assert::same($container->getService('http.requestFactory'), $container->getService('nette.httpRequestFactory'));
Assert::same($container->getService('http.request'), $container->getService('httpRequest'));
Assert::same($container->getService('http.response'), $container->getService('httpResponse'));
