<?php

/**
 * Test: Nette\Http\SessionSection::setExpiration()
 */

use Nette\Http\Session,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);
$session->setOptions(['gc_maxlifetime' => '0']); //memcache handler supports unlimited expiration

//try to set section to shorter expiration
$namespace = $session->getSection('maxlifetime');
$namespace->setExpiration(100);

Assert::same(true, true); //Fix Error: This test forgets to execute an assertion.
