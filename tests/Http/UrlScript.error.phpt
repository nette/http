<?php

declare(strict_types=1);

use Nette\Http\UrlScript;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::exception(function () {
	new UrlScript('http://nette.org/file.php?q=search', '/a/');
}, Nette\InvalidArgumentException::class);


Assert::exception(function () {
	new UrlScript('http://nette.org/file.php?q=search', 'a');
}, Nette\InvalidArgumentException::class);


Assert::exception(function () {
	new UrlScript('http://nette.org/dir/', '/d/');
}, Nette\InvalidArgumentException::class);


Assert::exception(function () {
	new UrlScript('http://nette.org/dir/', '/dir/index/');
}, Nette\InvalidArgumentException::class);
