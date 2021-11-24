<?php

/**
 * Test: Nette\Http\Session::autoStart()
 */

declare(strict_types=1);

use Nette\Http;
use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

// create fake session
$cookies = [session_name() => $sessionId = md5('3')];

$session = new Session(new Http\Request(new Http\UrlScript('http://nette.org'), [], [], $cookies), new Http\Response);
Assert::true($session->exists());
$session->autoStart(false);
Assert::false($session->isStarted());
Assert::false($session->exists());
Assert::false(file_exists(getTempDir() . '/sess_' . $sessionId));


$session->autoStart(true);
Assert::true($session->isStarted());
Assert::true($session->exists());
Assert::true(file_exists(getTempDir() . '/sess_' . $session->getId()));


$session->close();
$session->setOptions(['autoStart' => false]);
Assert::error(function () use ($session) {
	$session->autoStart(true);
}, E_USER_WARNING);
Assert::false($session->isStarted());
