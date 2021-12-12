<?php

declare(strict_types=1);

use Nette\Http\UrlImmutable;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('', function () {
	$url = new UrlImmutable('http://username%3A:password%3A@hostn%61me:60/p%61th/script.php?%61rg=value#%61nchor');

	$url = $url->withScheme('');
	Assert::same('//username%3A:password%3A@hostname:60/p%61th/script.php?arg=value#anchor', $url->absoluteUrl);

	$url = $url->withUser('name');
	Assert::same('//name:password%3A@hostname:60/p%61th/script.php?arg=value#anchor', $url->absoluteUrl);

	$url = $url->withPassword('secret');
	Assert::same('//name:secret@hostname:60/p%61th/script.php?arg=value#anchor', $url->absoluteUrl);

	$url = $url->withHost('localhost');
	Assert::same('//name:secret@localhost:60/p%61th/script.php?arg=value#anchor', $url->absoluteUrl);

	$url = $url->withPort(123);
	Assert::same('//name:secret@localhost:123/p%61th/script.php?arg=value#anchor', $url->absoluteUrl);

	$url = $url->withFragment('hello');
	Assert::same('//name:secret@localhost:123/p%61th/script.php?arg=value#hello', $url->absoluteUrl);

	$url = $url->withoutUserInfo();
	Assert::same('//localhost:123/p%61th/script.php?arg=value#hello', $url->absoluteUrl);
});


test('', function () {
	$url = new UrlImmutable('http://username%3A:password%3A@hostn%61me:60/p%61th/script.php?%61rg=value#%61nchor');

	$url = $url->withPath('');
	Assert::same('/', $url->getPath());

	$url = $url->withPath('/');
	Assert::same('/', $url->getPath());

	$url = $url->withPath('x');
	Assert::same('/x', $url->getPath());

	$url = $url->withPath('/x');
	Assert::same('/x', $url->getPath());

	$url = $url->withPath('');
	Assert::same('/', $url->getPath());

	$url = $url->withHost('')->withPath('');
	Assert::same('http:?arg=value#anchor', $url->absoluteUrl);

	$url = $url->withPath('');
	Assert::same('', $url->getPath());
});


test('', function () {
	$url = new UrlImmutable('http://hostname/path?arg=value');
	Assert::same('arg=value', $url->query);

	$url = $url->withQuery(['arg3' => 'value3']);
	Assert::same('arg3=value3', $url->query);

	$url = $url->withQuery([null]);
	Assert::same('http://hostname/path', $url->getAbsoluteUrl());

	$url = $url->withQuery('');
	Assert::same('http://hostname/path', $url->getAbsoluteUrl());

	$url = $url->withQuery('a=1');
	Assert::same('http://hostname/path?a=1', $url->getAbsoluteUrl());
});


test('', function () {
	$url = new UrlImmutable('http://hostname/path?arg=value');
	Assert::same('arg=value', $url->query);

	$url = $url->withQueryParameter('arg3', 'value3');
	Assert::same('arg=value&arg3=value3', $url->query);

	$url = $url->withQueryParameter('arg', 'value4');
	Assert::same('arg=value4&arg3=value3', $url->query);
});
