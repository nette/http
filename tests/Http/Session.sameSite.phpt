<?php

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new Nette\Http\RequestFactory;
$response = new Nette\Http\Response;
$session = new Nette\Http\Session($factory->fromGlobals(), $response);

$session->setOptions([
	'cookie_samesite' => 'Lax',
]);

$session->start();

Assert::same(
	['PHPSESSID=' . $session->getId() . '; path=/; HttpOnly; SameSite=Lax'],
	$response->getHeaders()['Set-Cookie']
);
