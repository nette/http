<?php declare(strict_types=1);

/**
 * Test: Nette\Http\Session error in session_start.
 */

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$cookies = [session_name() => '#'];

$session = new Http\Session(new Http\Request(new Http\UrlScript, [], [], $cookies), new Http\Response);

$session->start();

Assert::match('%[\w]+%', $session->getId());
