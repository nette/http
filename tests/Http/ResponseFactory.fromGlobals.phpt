<?php

/**
 * Test: Nette\Http\ResponseFactory::fromGlobals()
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$_SERVER['SERVER_PROTOCOL'] = 'HTTP/3.0';
http_response_code(123);
header('X-A: b');

$factory = new Http\ResponseFactory;
$response = $factory->fromGlobals();

Assert::same(123, $response->getCode());
Assert::same('3.0', $response->getProtocolVersion());

if (PHP_SAPI === 'cli') {
	Assert::same([], $response->getHeaders());
} else {
	Assert::same(['b'], $response->getHeaders()['X-A']);
}

Assert::same('', $response->getBody());

http_response_code(200);
