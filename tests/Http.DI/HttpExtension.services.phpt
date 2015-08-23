<?php

/**
 * Test: HttpExtension.
 */

use Nette\DI;
use Nette\Bridges\HttpDI\HttpExtension;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$compiler = new DI\Compiler;
$compiler->addExtension('http', new HttpExtension);
eval($compiler->compile([], 'Container1'));

$container = new Container1;

Assert::type(Nette\Http\RequestFactory::class, $container->getService('http.requestFactory'));
Assert::type(Nette\Http\Request::class, $container->getService('http.request'));
Assert::type(Nette\Http\Response::class, $container->getService('http.response'));
Assert::type(Nette\Http\Context::class, $container->getService('http.context'));

// aliases
Assert::same($container->getService('http.requestFactory'), $container->getService('nette.httpRequestFactory'));
Assert::same($container->getService('http.request'), $container->getService('httpRequest'));
Assert::same($container->getService('http.response'), $container->getService('httpResponse'));
Assert::same($container->getService('http.context'), $container->getService('nette.httpContext'));
