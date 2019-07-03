<?php

/**
 * Test: Nette\Http\RequestFactory and user and password.
 */

declare(strict_types=1);

use Nette\Http\RequestFactory;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$_SERVER = [
	'PHP_AUTH_USER' => 'user',
	'PHP_AUTH_PW' => 'password',
];
$factory = new RequestFactory;
Assert::same('user', $factory->createHttpRequest()->getUrl()->getUser());
Assert::same('password', $factory->createHttpRequest()->getUrl()->getPassword());


$_SERVER = [];
$factory = new RequestFactory;
Assert::same('', $factory->createHttpRequest()->getUrl()->getUser());
Assert::same('', $factory->createHttpRequest()->getUrl()->getPassword());
