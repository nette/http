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
Assert::same('user', $factory->fromGlobals()->getUrl()->getUser());
Assert::same('password', $factory->fromGlobals()->getUrl()->getPassword());


$_SERVER = [];
$factory = new RequestFactory;
Assert::same('', $factory->fromGlobals()->getUrl()->getUser());
Assert::same('', $factory->fromGlobals()->getUrl()->getPassword());
