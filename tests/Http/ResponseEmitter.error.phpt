<?php

/**
 * Test: Nette\Http\ResponseEmitter errors.
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$response = new Http\Response;
$emitter = new Http\ResponseEmitter;

ob_start();
echo ' ';
$emitter->sendHeaders($response); // full buffer
ob_end_clean();


if (PHP_SAPI === 'cli') {
	Assert::noError(function () use ($emitter, $response) {
		ob_start(null, 4096);
		echo '  ';
		$emitter->sendHeaders($response);
	});

	Assert::error(function () use ($emitter, $response) {
		ob_flush();
		$emitter->sendHeaders($response);
	}, E_WARNING, 'Cannot modify header information - headers already sent by (output started at ' . __FILE__ . ':' . (__LINE__ - 2) . ')');

} else {
	Assert::error(function () use ($emitter, $response) {
		ob_start(null, 4096);
		echo '  ';
		$emitter->sendHeaders($response);
	}, E_USER_NOTICE, 'Possible problem: you are sending a HTTP header while already having some data in output buffer%a%');

	Assert::noError(function () use ($emitter, $response) {
		$emitter->warnOnBuffer = false;
		$emitter->sendHeaders($response);
	});

	Assert::exception(function () use ($emitter, $response) {
		ob_flush();
		$emitter->sendHeaders($response);
	}, Nette\InvalidStateException::class, 'Cannot send header after HTTP headers have been sent (output started at ' . __FILE__ . ':' . (__LINE__ - 2) . ').');
}
