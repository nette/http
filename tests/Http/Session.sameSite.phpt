<?php

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Cookies are not available in CLI');
}


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->createHttpRequest(), new Nette\Http\Response);

// is samesite=Lax by default
$session->start();

Assert::contains(
	PHP_VERSION_ID >= 70300
		? 'Set-Cookie: PHPSESSID=' . $session->getId() . '; path=/; HttpOnly; SameSite=Lax'
		: 'Set-Cookie: PHPSESSID=' . $session->getId() . '; path=/; SameSite=Lax; HttpOnly',
	headers_list()
);
