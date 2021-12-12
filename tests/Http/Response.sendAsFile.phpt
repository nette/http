<?php

/**
 * Test: Nette\Http\Response::sendAsFile().
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Headers are not available in CLI');
}


$response = new Http\Response;

$old = headers_list();
$response->sendAsFile('file.name');
$headers = array_values(array_diff(headers_list(), $old));
Assert::same([
	'Content-Disposition: attachment; filename="file.name"; filename*=utf-8\'\'file.name',
], $headers);


$old = headers_list();
$response->sendAsFile('žluťoučký"\' name');
$headers = array_values(array_diff(headers_list(), $old));
Assert::same([
	'Content-Disposition: attachment; filename="žluťoučký\' name"; filename*=utf-8\'\'%C5%BElu%C5%A5ou%C4%8Dk%C3%BD%22%27%20name',
], $headers);
