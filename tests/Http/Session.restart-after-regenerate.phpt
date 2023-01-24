<?php

/**
 * Test: Nette\Http\Session is preserved after regenerateId and restarting
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$cookies = [session_name() => $sessionId = md5('3')];
file_put_contents(getTempDir() . '/sess_' . $sessionId, sprintf('__NF|a:2:{s:4:"Time";i:%s;s:4:"DATA";a:1:{s:4:"temp";a:1:{s:5:"value";s:3:"yes";}}}', time() - 1000));

$session = new Http\Session(new Http\Request(new Http\UrlScript, [], [], $cookies), new Http\Response);

$session->start();
Assert::same($sessionId, $session->getId());
Assert::same('yes', $session->getSection('temp')->get('value'));

$session->regenerateId();
Assert::notSame($sessionId, $session->getId());
Assert::same(session_id(), $session->getId());
$session->close();

$session->start();
Assert::same('yes', $session->getSection('temp')->get('value'));

Assert::true(file_exists(getTempDir() . '/sess_' . $session->getId()));
Assert::count(1, glob(getTempDir() . '/sess_*'));
