<?php

/**
 * Test: SessionExtension.
 */

declare(strict_types=1);

use Nette\Bridges\HttpDI\HttpExtension;
use Nette\Bridges\HttpDI\SessionExtension;
use Nette\DI;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


class TestHandler extends SessionHandler
{
	public $called = false;


	public function open(string $savePath, string $sessionName): bool
	{
		$this->called = true;
		return parent::open($savePath, $sessionName);
	}
}


$compiler = new DI\Compiler;
$compiler->addExtension('foo', new HttpExtension);
$compiler->addExtension('session', new SessionExtension(false, PHP_SAPI === 'cli'));

$loader = new DI\Config\Loader;
$config = $loader->load(Tester\FileMock::create('
session:
	handler: @handler

services:
	foo.request: Nette\Http\Request(Nette\Http\UrlScript("http://www.nette.org"))
	handler: TestHandler
', 'neon'));

eval($compiler->addConfig($config)->compile());

$container = new Container;
$container->getService('session')->start();

Assert::true($container->getService('handler')->called);
