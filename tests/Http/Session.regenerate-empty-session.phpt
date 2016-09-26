<?php

/**
 * Test: Nette\Http\Session::regenerateId() regenerate empty session
 */

use Nette\Http;
use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// create fake session
$cookies = ['PHPSESSID' => $sessionId = md5('3')];
file_put_contents(TEMP_DIR . '/sess_' . $sessionId, '__NF|a:1:{s:4:"DATA";a:1:{s:4:"temp";a:1:{s:5:"value";s:3:"yes";}}}');

$session = new Session(new Http\Request(new Http\UrlScript('http://nette.org'), NULL, [], [], $cookies), new Http\Response());
$session->start();
Assert::same('yes', $session->getSection('temp')->value);

$newSessionId = $session->getId();
$session->close();

// session was regenerated
Assert::false(file_exists(TEMP_DIR . '/sess_' . $sessionId));
Assert::true(file_exists(TEMP_DIR . '/sess_' . $newSessionId));
Assert::count(1, glob(TEMP_DIR . '/sess_*'));
