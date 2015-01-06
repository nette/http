<?php

/**
 * Test: Nette\Http\Request URI.
 */

use Nette\Http,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$request = new Http\Request(new Http\UrlScript('http://localhost'));
$request->getUrl()->setPath('/test');
Assert::same('/', $request->getUrl()->getPath());
