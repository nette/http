<?php

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new Http\ResponseFactory;
$response = $factory->fromUrl('https://nette.org');

Assert::same(200, $response->getCode());
Assert::same('1.1', $response->getProtocolVersion());
Assert::same('OK', $response->getReasonPhrase());
Assert::same(['SAMEORIGIN'], $response->getHeaders()['X-Frame-Options']);
Assert::contains('Nette Foundation', $response->getBody());
