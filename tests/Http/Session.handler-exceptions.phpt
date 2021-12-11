<?php

/**
 * Test: Nette\Http\Session handle storage exceptions.
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class ThrowsOnReadHandler extends SessionHandler
{
	#[ReturnTypeWillChange]
	public function open($save_path, $session_id)
	{
		return true; // never throw an exception from here, the universe might implode
	}


	#[ReturnTypeWillChange]
	public function read($session_id)
	{
		throw new RuntimeException("Session can't be started for whatever reason!");
	}
}


$session = new Nette\Http\Session(new Http\Request(new Http\UrlScript('http://nette.org')), new Http\Response);
$session->setHandler(new ThrowsOnReadHandler);

Assert::exception(function () use ($session) {
	$session->start();
}, RuntimeException::class, 'Session can\'t be started for whatever reason!');

Assert::exception(function () use ($session) {
	$session->start();
}, RuntimeException::class, 'Session can\'t be started for whatever reason!');

$session->setHandler(new SessionHandler);
$session->start();
