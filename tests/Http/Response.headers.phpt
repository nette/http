<?php

/**
 * Test: Nette\Http\Response headers
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI === 'cli') {
	Tester\Environment::skip('Headers are not available in CLI');
}


$response = new Http\Response;

$response->setHeader('replace', 'one');
$response->setHeader('replace', 'two');

$response->addHeader('append', 'one');
$response->addHeader('append', 'two');


Assert::same('two', $response->getHeader('replace'));
Assert::same('two', $response->getHeader('REPLACE'));

Assert::same('one', $response->getHeader('append'));
Assert::same('one', $response->getHeader('APPEND'));

$headers = $response->getHeaders();
Assert::contains('two', $headers['replace']);
Assert::contains('two', $headers['append']);


$response->deleteHeader('append');
$headers = $response->getHeaders();
Assert::contains('two', $headers['replace']);
Assert::false(isset($headers['append']));
