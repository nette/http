<?php declare(strict_types=1);

/**
 * Test: Nette\Http\Session setOptions error.
 */

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$factory = new Nette\Http\RequestFactory;
$session = new Nette\Http\Session($factory->fromGlobals(), new Nette\Http\Response);
$session->start();

Assert::exception(
	fn() => $session->setOptions([
		'gc_malifetime' => 123,
	]),
	Nette\InvalidStateException::class,
	"Invalid session configuration option 'gc_malifetime', did you mean 'gc_maxlifetime'?",
);

Assert::exception(
	fn() => $session->setOptions([
		'cookieDoman' => '.domain.com',
	]),
	Nette\InvalidStateException::class,
	"Invalid session configuration option 'cookieDoman', did you mean 'cookieDomain'?",
);


Assert::exception(
	fn() => $session->setOptions([
		'gcMaxlifetime' => 123,
	]),
	Nette\InvalidStateException::class,
	"Unable to set 'session.gc_maxlifetime' to value '123' (current value is '%a%') when session has been started.",
);
