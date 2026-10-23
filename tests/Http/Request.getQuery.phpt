<?php declare(strict_types=1);

/**
 * Test: Nette\Http\Request::getQuery() and getPost()
 */

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('query parameters', function () {
	$request = new Http\Request(new Http\UrlScript('http://nette.org/?a=1&0=x'));
	$key = null;

	Assert::same(['a' => '1', 0 => 'x'], $request->getQuery());
	Assert::same(['a' => '1', 0 => 'x'], $request->getQuery($key));
	Assert::same('1', $request->getQuery('a'));
	Assert::null($request->getQuery('missing'));
});


test('post parameters', function () {
	$request = new Http\Request(new Http\UrlScript('http://nette.org/'), post: ['a' => '1', '' => 'empty']);
	$key = null;

	Assert::same(['a' => '1', '' => 'empty'], $request->getPost());
	Assert::same(['a' => '1', '' => 'empty'], $request->getPost($key));
	Assert::same('1', $request->getPost('a'));
	Assert::null($request->getPost('missing'));
});
