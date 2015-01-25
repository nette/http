<?php

/**
 * Test: Nette\Http\UrlScript modify.
 */

use Nette\Http\UrlScript,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$url = new UrlScript('http://nette.org:8080/file.php?q=search');
$url = $url->setPath('/test/');
$url = $url->setScriptPath('/test/index.php');

Assert::same( '/test/index.php',  $url->scriptPath );
Assert::same( 'http://nette.org:8080/test/',  $url->baseUrl );
Assert::same( '/test/',  $url->basePath );
Assert::same( '?q=search',  $url->relativeUrl );
Assert::same( '',  $url->pathInfo );
Assert::same( 'http://nette.org:8080/test/?q=search',  $url->absoluteUrl);
