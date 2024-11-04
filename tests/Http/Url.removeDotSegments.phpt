<?php

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('begins with /', function () {
	Assert::same('/', Url::removeDotSegments('/'));
	Assert::same('/file', Url::removeDotSegments('/file'));
	Assert::same('/file/', Url::removeDotSegments('/file/'));

	Assert::same('/', Url::removeDotSegments('/.'));
	Assert::same('/', Url::removeDotSegments('/./'));

	Assert::same('/file/', Url::removeDotSegments('/file/.'));
	Assert::same('/', Url::removeDotSegments('/file/..'));
	Assert::same('/', Url::removeDotSegments('/file/../'));
	Assert::same('/file', Url::removeDotSegments('/./file'));
});


test('not begins with /', function () {
	Assert::same('', Url::removeDotSegments(''));
	Assert::same('file', Url::removeDotSegments('file'));
	Assert::same('file/', Url::removeDotSegments('file/'));

	Assert::same('', Url::removeDotSegments('.'));
	Assert::same('', Url::removeDotSegments('./'));

	Assert::same('file/', Url::removeDotSegments('file/.'));
	Assert::same('', Url::removeDotSegments('file/..'));
	Assert::same('', Url::removeDotSegments('file/../'));
	Assert::same('file', Url::removeDotSegments('./file'));
});


test('incorrect ..', function () {
	Assert::same('/', Url::removeDotSegments('/file/../..'));
	Assert::same('/', Url::removeDotSegments('/file/../../'));
	Assert::same('/bar', Url::removeDotSegments('/file/../../bar'));
	Assert::same('/file', Url::removeDotSegments('/../file'));
	Assert::same('/bar', Url::removeDotSegments('/file/./.././.././bar'));
	Assert::same('/bar/', Url::removeDotSegments('/file/./.././.././bar/'));
});


test('double slash', function () {
	Assert::same('//', Url::removeDotSegments('//'));
	Assert::same('//foo//', Url::removeDotSegments('//foo//'));
	Assert::same('//foo//', Url::removeDotSegments('//foo//..//'));
});
