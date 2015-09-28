<?php

/**
 * Test: Nette\Http\Session handle storage exceptions.
 */

use Nette\Http;
use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


class ThrowsOnReadHandler extends \SessionHandler
{

	public function open($save_path, $session_id)
	{
		return TRUE; // never throw an exception from here, the universe might implode
	}


	public function read($session_id)
	{
		throw new RuntimeException("Session can't be started for whatever reason!");
	}

}


$session = new Nette\Http\Session(new Http\Request(new Http\UrlScript('http://nette.org')), new Http\Response);
$session->setHandler(new ThrowsOnReadHandler);

Assert::exception(function () use ($session) {
	$session->start();
}, 'RuntimeException', 'Session can\'t be started for whatever reason!');

Assert::exception(function () use ($session) {
	$session->start();
}, 'RuntimeException', 'Session can\'t be started for whatever reason!');

$session->setHandler(new \SessionHandler());
$session->start();
