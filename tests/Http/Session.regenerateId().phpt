<?php

/**
 * Test: Nette\Http\Session::regenerateId()
 */

declare(strict_types=1);

use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$response = new Nette\Http\Response;
$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), $response);

$path = rtrim(ini_get('session.save_path'), '/\\') . '/sess_';

$session->start();
$oldId = $session->getId();
Assert::true(is_file($path . $oldId));
$ref = &$_SESSION['var'];
$ref = 10;

$session->regenerateId();
$newId = $session->getId();
Assert::notSame($newId, $oldId);
Assert::true(is_file($path . $newId));

$ref = 20;
Assert::same(20, $_SESSION['var']);

Assert::same(['PHPSESSID=' . $newId . '; path=/; HttpOnly'], $response->getHeaders()['Set-Cookie']);
