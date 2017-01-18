<?php

/**
 * Test: Nette\Http\SessionSection::setExpiration()
 */

declare(strict_types=1);

use Nette\Http\Session;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);

$session->setExpiration('+10 seconds');

test(function () use ($session) { // try to expire whole namespace
	$namespace = $session->getSection('expire');
	$namespace->a = 'apple';
	$namespace->p = 'pear';
	$namespace['o'] = 'orange';
	$namespace->setExpiration('+ 1 seconds');

	$session->close();
	sleep(2);
	$session->start();

	$namespace = $session->getSection('expire');
	Assert::same('', http_build_query($namespace->getIterator()));
});


test(function () use ($session) { // try to expire only 1 of the keys
	$namespace = $session->getSection('expireSingle');
	$namespace->setExpiration(1, 'g');
	$namespace->g = 'guava';
	$namespace->p = 'plum';

	$session->close();
	sleep(2);
	$session->start();

	$namespace = $session->getSection('expireSingle');
	Assert::same('p=plum', http_build_query($namespace->getIterator()));
});


// small expiration
Assert::error(function () use ($session) {
	$namespace = $session->getSection('tmp');
	$namespace->setExpiration(100);
}, E_USER_NOTICE, 'The expiration time is greater than the session expiration 10 seconds');
