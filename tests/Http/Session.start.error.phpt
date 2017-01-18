<?php

/**
 * Test: Nette\Http\Session error in session_start.
 */

declare(strict_types=1);

use Nette\Http\Session;
use Nette\Http\SessionSection;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


ini_set('session.save_path', ';;;');
ini_set('session.gc_probability', '0');


$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);

Assert::exception(function () use ($session) {
	$session->start();
}, Nette\InvalidStateException::class);
