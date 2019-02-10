<?php

/**
 * Test: Nette\Http\UrlScript modify.
 */

declare(strict_types=1);

use Nette\Http\UrlScript;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$url = new UrlScript('http://nette.org:8080/file.php?q=search');
$url->path = '/test/';
$url->scriptPath = '/test/index.php';

Assert::same('/test/index.php', $url->scriptPath);
Assert::same('http://nette.org:8080/test/', $url->baseUrl);
Assert::same('/test/', $url->basePath);
Assert::same('?q=search', $url->relativeUrl);
Assert::same('', $url->pathInfo);
Assert::same('http://nette.org:8080/test/?q=search', $url->absoluteUrl);


$url = new UrlScript('http://nette.org:8080/www/about');
$url->scriptPath = '/www/';

Assert::same('/www/about', $url->path);
Assert::same('/www/', $url->scriptPath);
Assert::same('about', $url->relativePath);
Assert::same('about', $url->pathInfo);


$url = new UrlScript('http://nette.org:8080/www/index.php');
$url->scriptPath = '/www/index.php';

Assert::same('/www/index.php', $url->path);
Assert::same('/www/index.php', $url->scriptPath);
Assert::same('index.php', $url->relativePath);
Assert::same('', $url->pathInfo);
