<?php

declare(strict_types=1);

use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$url = new UrlScript('http://nette.org:8080/test/?q=search', '/test/index.php');
	Assert::same('/test/', $url->basePath);
	Assert::same('/test/index.php', $url->scriptPath);

	$url = $url->withPath('/index.php');
	Assert::same('/', $url->basePath);
	Assert::same('/index.php', $url->scriptPath);

	$url = $url->withPath('/test/', '/test/index.php');
	Assert::same('/test/', $url->basePath);
	Assert::same('/test/index.php', $url->scriptPath);
});


Assert::exception(function () {
	$url = new UrlScript('http://nette.org:8080/test/?q=search', '/test/index.php');
	$url->withPath('/test/', '/test/index/');
}, Nette\InvalidArgumentException::class);
