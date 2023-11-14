<?php

/**
 * Test: Nette\Http\Session::regenerateId() regenerate empty session
 */

declare(strict_types=1);

use Nette\Http;
use Nette\Http\Session;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// create fake session
$cookies = [session_name() => $sessionId = md5('3')];
file_put_contents(getTempDir() . '/sess_' . $sessionId, '__NF|a:1:{s:4:"DATA";a:1:{s:4:"temp";a:1:{s:5:"value";s:3:"yes";}}}');

$session = new Session(new Http\Request(new Http\UrlScript('http://nette.org'), [], [], $cookies), new Http\Response);
$session->setOptions(['readAndClose' => true]);
$session->start();
Assert::same('yes', $session->getSection('temp')->get('value'));

// session was not regenerated
Assert::same($session->getId(), $sessionId);
Assert::true(file_exists(getTempDir() . '/sess_' . $sessionId));
