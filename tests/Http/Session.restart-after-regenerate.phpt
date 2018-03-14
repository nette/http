<?php

/**
 * Test: Nette\Http\Session is preserved after regenerateId and restarting
 */

use Nette\Http;
use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';

$_COOKIE['PHPSESSID'] = $leet = md5('1337');

// create fake session
$sessionId = md5('1');
session_id($sessionId);
file_put_contents(TEMP_DIR . '/sess_' . $sessionId, sprintf('__NF|a:2:{s:4:"Time";i:%s;s:4:"DATA";a:1:{s:4:"temp";a:1:{s:5:"value";s:3:"yes";}}}', time() - 1000));

$session = new Session(new Http\Request(new Http\UrlScript), new Http\Response);

$session->start();
Assert::same($sessionId, $session->getId());
Assert::same(['PHPSESSID' => $leet], $_COOKIE);

Assert::same('yes', $session->getSection('temp')->value);
$session->regenerateId();
Assert::notSame($sessionId, $session->getId());
$newSessionId = session_id();
Assert::same($newSessionId, $session->getId());
$session->close();

$session->start();
Assert::same('yes', $session->getSection('temp')->value);
Assert::same($newSessionId, $session->getId());

// new session still exists
Assert::true(file_exists(TEMP_DIR . '/sess_' . $newSessionId));
Assert::count(1, glob(TEMP_DIR . '/sess_*'));
