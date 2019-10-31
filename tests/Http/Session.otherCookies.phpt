<?php declare(strict_types=1);

/**
 * Test: Session keeps the cookies already set on the response.
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$factory = new Nette\Http\RequestFactory;
$response = new Nette\Http\Response;
$response->addHeader('set-cookie', 'a=1');
$session = new Nette\Http\Session($factory->fromGlobals(), $response);

$session->start();

Assert::same(
	['a=1', 'PHPSESSID=' . $session->getId() . '; path=/; HttpOnly; SameSite=Lax'],
	$response->getHeaders()['Set-Cookie'],
);
