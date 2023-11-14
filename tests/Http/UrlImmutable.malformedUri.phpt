<?php

/**
 * Test: Nette\Http\UrlImmutable malformed URI.
 */

declare(strict_types=1);

use Nette\Http\UrlImmutable;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::exception(
	fn() => new UrlImmutable('http:///'),
	InvalidArgumentException::class,
	"Malformed or unsupported URI 'http:///'.",
);
