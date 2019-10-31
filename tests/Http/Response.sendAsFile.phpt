<?php declare(strict_types=1);

/**
 * Test: Nette\Http\Response::sendAsFile().
 */

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$response = new Http\Response;

$response->sendAsFile('file.name');
Assert::same([
	'Content-Disposition' => ['attachment; filename="file.name"; filename*=utf-8\'\'file.name'],
], $response->getHeaders());


$response->sendAsFile('žluťoučký"\' name');
Assert::same([
	'Content-Disposition' => [
		'attachment; filename="žluťoučký\' name"; filename*=utf-8\'\'%C5%BElu%C5%A5ou%C4%8Dk%C3%BD%22%27%20name',
	],
], $response->getHeaders());
