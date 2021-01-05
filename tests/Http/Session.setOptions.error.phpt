<?php

/**
 * Test: Nette\Http\Session setOptions error.
 */

declare(strict_types=1);

use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->fromGlobals(), new Nette\Http\Response);
$session->start();

Assert::exception(function () use ($session) {
	$session->setOptions([
		'gc_malifetime' => 123,
	]);
}, Nette\InvalidStateException::class, "Invalid session configuration option 'gc_malifetime', did you mean 'gc_maxlifetime'?");

Assert::exception(function () use ($session) {
	$session->setOptions([
		'cookieDoman' => '.domain.com',
	]);
}, Nette\InvalidStateException::class, "Invalid session configuration option 'cookieDoman', did you mean 'cookieDomain'?");
