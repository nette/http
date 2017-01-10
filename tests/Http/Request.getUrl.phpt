<?php

/**
 * Test: Nette\Http\Request URI.
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$request = new Http\Request(new Http\UrlScript('http://localhost'));
$request->getUrl()->setPath('/test');
Assert::same('/', $request->getUrl()->getPath());
