<?php

/**
 * Test: Nette\Http\Url malformed URI.
 */

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::exception(
	fn() => new Url('http:///'),
	InvalidArgumentException::class,
	"Malformed or unsupported URI 'http:///'.",
);
