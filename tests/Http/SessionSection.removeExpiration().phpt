<?php

/**
 * Test: Nette\Http\SessionSection::removeExpiration()
 */

declare(strict_types=1);

use Nette\Http\Session;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);
$session->setExpiration('+10 seconds');

$section = $session->getSection('expireRemove');
$section->a = 'apple';
$section->b = 'banana';

$section->setExpiration('+2 seconds', 'a');
$section->removeExpiration('a');

$session->close();
sleep(3);
$session->start();

$section = $session->getSection('expireRemove');
Assert::same('a=apple&b=banana', http_build_query($section->getIterator()));
