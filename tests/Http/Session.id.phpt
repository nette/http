<?php

/**
 * Test: Nette\Http\Session accepts cookie from Http\IRequest
 */

declare(strict_types=1);

use Nette\Http;
use Nette\Http\Session;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$sessionName = session_name();
$_COOKIE[$sessionName] = $leet = md5('1337');

// create fake session
$cookies = [$sessionName => $sessionId = md5('1')];
file_put_contents(getTempDir() . '/sess_' . $sessionId, sprintf('__NF|a:2:{s:4:"Time";i:%s;s:4:"DATA";a:1:{s:4:"temp";a:1:{s:5:"value";s:3:"yes";}}}', time() - 1000));

$session = new Session(new Http\Request(new Http\UrlScript('http://nette.org'), [], [], $cookies), new Http\Response);

$session->start();
Assert::same($sessionId, $session->getId());
Assert::same([$sessionName => $leet], $_COOKIE);

Assert::same('yes', $session->getSection('temp')->value);
$session->close();

// session was not regenerated
Assert::true(file_exists(getTempDir() . '/sess_' . $sessionId));
Assert::count(1, glob(getTempDir() . '/sess_*'));
