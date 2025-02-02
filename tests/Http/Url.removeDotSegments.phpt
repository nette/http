<?php

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('dot segment removal in absolute paths', function () {
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


test('dot segment removal in relative paths', function () {
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


test('excessive parent directory traversal handling', function () {
	Assert::same('/', Url::removeDotSegments('/file/../..'));
	Assert::same('/', Url::removeDotSegments('/file/../../'));
	Assert::same('/bar', Url::removeDotSegments('/file/../../bar'));
	Assert::same('/file', Url::removeDotSegments('/../file'));
	Assert::same('/bar', Url::removeDotSegments('/file/./.././.././bar'));
	Assert::same('/bar/', Url::removeDotSegments('/file/./.././.././bar/'));
});


test('double slash preservation', function () {
	Assert::same('//', Url::removeDotSegments('//'));
	Assert::same('//foo//', Url::removeDotSegments('//foo//'));
	Assert::same('//foo//', Url::removeDotSegments('//foo//..//'));
});
