<?php

/**
 * Test: Nette\Http\Session error in session_start.
 */

use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$_COOKIE['PHPSESSID'] = '#';
session_id('#');


$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);

$session->start();

Assert::match('%[\w]+%', $session->getId());
