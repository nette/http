<?php

/**
 * Test: Nette\Http\Session error caused by a malicious 'nette-browser' cookie
 */

use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

class MockResponse extends Nette\Http\Response
{
	public function setCookie($name, $value, $time, $path = NULL, $domain = NULL, $secure = NULL, $httpOnly = NULL)
	{
		Assert::type('string', $value);
		return parent::setCookie($name, $value, $time, $path, $domain, $secure, $httpOnly);
	}
}


$_COOKIE['nette-browser'] = ['invalid'];

$requestFactory = new Nette\Http\RequestFactory();
$request = $requestFactory->createHttpRequest();

$session = new Session($request, new MockResponse);
$session->start();
