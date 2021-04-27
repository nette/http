<?php

/**
 * Test: Nette\Http\Response errors.
 * @phpIni output_buffering=0
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$response = new Http\Response;
$response->setHeader('A', 'b'); // no output

ob_start();
echo ' ';
$response->setHeader('A', 'b'); // full buffer
ob_end_clean();


if (PHP_SAPI === 'cli') {
	Assert::noError(function () use ($response) {
		ob_start(null, 4096);
		echo '  ';
		$response->setHeader('A', 'b');
	});

	Assert::error(function () use ($response) {
		ob_flush();
		$response->setHeader('A', 'b');
	}, E_WARNING, 'Cannot modify header information - headers already sent by (output started at ' . __FILE__ . ':' . (__LINE__ - 2) . ')');

} else {
	Assert::error(function () use ($response) {
		ob_start(null, 4096);
		echo '  ';
		$response->setHeader('A', 'b');
	}, E_USER_NOTICE, 'Possible problem: you are sending a HTTP header while already having some data in output buffer%a%');

	Assert::noError(function () use ($response) {
		$response->warnOnBuffer = false;
		$response->setHeader('A', 'b');
	});

	Assert::exception(function () use ($response) {
		ob_flush();
		$response->setHeader('A', 'b');
	}, Nette\InvalidStateException::class, 'Cannot send header after HTTP headers have been sent (output started at ' . __FILE__ . ':' . (__LINE__ - 2) . ').');
}
