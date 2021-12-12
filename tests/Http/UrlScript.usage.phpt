<?php

/**
 * Test: Nette\Http\UrlScript parse.
 */

declare(strict_types=1);

use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$url = new UrlScript('http://nette.org:8080/file.php?q=search');
	Assert::same('/file.php', $url->scriptPath);
	Assert::same('http://nette.org:8080/', $url->baseUrl);
	Assert::same('/', $url->basePath);
	Assert::same('file.php?q=search', $url->relativeUrl);
	Assert::same('file.php', $url->relativePath);
	Assert::same('', $url->pathInfo);
});


test('', function () {
	$url = new UrlScript('http://nette.org:8080/file.php?q=search', '/');
	Assert::same('/', $url->scriptPath);
	Assert::same('http://nette.org:8080/', $url->baseUrl);
	Assert::same('/', $url->basePath);
	Assert::same('file.php?q=search', $url->relativeUrl);
	Assert::same('file.php', $url->relativePath);
	Assert::same('file.php', $url->pathInfo);
});


test('', function () {
	$url = new UrlScript('http://nette.org:8080/test/?q=search', '/test/index.php');
	Assert::same('/test/index.php', $url->scriptPath);
	Assert::same('http://nette.org:8080/test/', $url->baseUrl);
	Assert::same('/test/', $url->basePath);
	Assert::same('?q=search', $url->relativeUrl);
	Assert::same('', $url->relativePath);
	Assert::same('', $url->pathInfo);
	Assert::same('http://nette.org:8080/test/?q=search', $url->absoluteUrl);
});


test('', function () {
	$url = new UrlScript('http://nette.org:8080/www/about', '/www/');
	Assert::same('/www/about', $url->path);
	Assert::same('/www/', $url->scriptPath);
	Assert::same('about', $url->relativePath);
	Assert::same('about', $url->pathInfo);
});


test('', function () {
	$url = new UrlScript('http://nette.org:8080/www/index.php', '/www/index.php');
	Assert::same('/www/index.php', $url->path);
	Assert::same('/www/index.php', $url->scriptPath);
	Assert::same('index.php', $url->relativePath);
	Assert::same('', $url->pathInfo);
});
