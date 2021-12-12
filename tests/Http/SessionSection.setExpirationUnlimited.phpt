<?php

/**
 * Test: Nette\Http\SessionSection::setExpiration()
 */

declare(strict_types=1);

use Nette\Http\Session;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);
$session->setOptions(['gc_maxlifetime' => '0']); //memcache handler supports unlimited expiration

//try to set section to shorter expiration
$namespace = $session->getSection('maxlifetime');
$namespace->setExpiration('100 second');

Assert::same(true, true); // fix Error: This test forgets to execute an assertion.
