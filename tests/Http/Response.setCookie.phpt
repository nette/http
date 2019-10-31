<?php

/**
 * Test: Nette\Http\Response::setCookie().
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';



$response = new Http\Response;

$response->setCookie('test', 'value', 0);
Assert::same([
	'Set-Cookie' => ['test=value; path=/; HttpOnly'],
], $response->getHeaders());


$response->setCookie('test', 'newvalue', 0);
Assert::same([
	'Set-Cookie' => ['test=value; path=/; HttpOnly', 'test=newvalue; path=/; HttpOnly'],
], $response->getHeaders());


$response->setCookie('test', 'newvalue', 0, null, null, null, null, 'Lax');
Assert::same([
	'Set-Cookie' => ['test=value; path=/; HttpOnly', 'test=newvalue; path=/; HttpOnly', 'test=newvalue; path=/; HttpOnly; SameSite=Lax'],
], $response->getHeaders());
