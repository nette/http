<?php

/**
 * Test: Nette\Http\Session accepts cookie from Http\IRequest
 */

use Nette\Http,
	Nette\Http\Session,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$_COOKIE['PHPSESSID'] = $leet = md5(1337);

// create fake session
$cookies = ['PHPSESSID' => $sessionId = md5(1), 'nette-browser' => $B = substr(md5(2), 0, 10)];
file_put_contents(TEMP_DIR . '/sess_' . $sessionId, sprintf('__NF|a:3:{s:4:"Time";i:%s;s:1:"B";s:10:"%s";s:4:"DATA";a:1:{s:4:"temp";a:1:{s:5:"value";s:3:"yes";}}}', time() - 1000, $B));

$session = new Session(new Http\Request(new Http\UrlScript('http://nette.org'), NULL, [], [], $cookies), new Http\Response());

$session->start();
Assert::same($sessionId, $session->getId());
Assert::same(['PHPSESSID' => $leet], $_COOKIE);

Assert::same('yes', $session->getSection('temp')->value);
$session->close();

// session was not regenerated
Assert::true(file_exists(TEMP_DIR . '/sess_' . $sessionId));
Assert::count(1, glob(TEMP_DIR . '/sess_*'));
