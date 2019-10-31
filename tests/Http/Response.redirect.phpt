<?php

/**
 * Test: Nette\Http\Response redirect.
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$response = new Http\Response;

$response->redirect('http://nette.org/&');
Assert::same("<h1>Redirect</h1>\n\n<p><a href=\"http://nette.org/&amp;\">Please click here to continue</a>.</p>", $response->getBody());

Assert::same(['Location' => ['http://nette.org/&']], $response->getHeaders());


$response->redirect(' javascript:alert(1)');
Assert::same('', $response->getBody());

Assert::same(['Location' => ['javascript:alert(1)']], $response->getHeaders());
