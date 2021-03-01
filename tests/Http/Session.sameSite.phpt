<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Cookies are not available in CLI');
}


$factory = new Nette\Http\RequestFactory;
$response = new Nette\Http\Response;
$session = new Nette\Http\Session($factory->fromGlobals(), $response);

$session->setOptions([
	'cookie_samesite' => $response::SameSiteLax,
]);

$session->start();

Assert::contains('Set-Cookie: PHPSESSID=' . $session->getId() . '; path=/; HttpOnly; SameSite=Lax', headers_list());
