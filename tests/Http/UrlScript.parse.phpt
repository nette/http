<?php

/**
 * Test: Nette\Http\UrlScript parse.
 */

use Nette\Http\UrlScript;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$url = new UrlScript('http://nette.org:8080/file.php?q=search');
Assert::same('/file.php', $url->scriptPath);
Assert::same('http://nette.org:8080/',  $url->baseUrl);
Assert::same('/', $url->basePath);
Assert::same('file.php?q=search',  $url->relativeUrl);
Assert::same('',  $url->pathInfo);
