<?php

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$response = new Http\Response;
$response->setProtocolVersion('3.0');
$response->setCode(123, 'My Reason');
$response->setHeader('X-A', 'a');
$response->addHeader('X-A', 'b');
$response->setBody('Hello');

Assert::same(
	"HTTP/3.0 123 My Reason\r\nX-A: a\r\nX-A: b\r\n\r\nHello",
	(string) $response
);
