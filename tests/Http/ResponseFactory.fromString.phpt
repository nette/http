<?php

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new Http\ResponseFactory;
$response = $factory->fromString("HTTP/3.0 123 My Reason\r\nX-A: a\r\nX-A: b\r\n\r\nHello");

Assert::same(123, $response->getCode());
Assert::same('3.0', $response->getProtocolVersion());
Assert::same('My Reason', $response->getReasonPhrase());

Assert::same(['X-A' => ['a', 'b']], $response->getHeaders());

Assert::same('Hello', $response->getBody());
