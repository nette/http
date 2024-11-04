<?php

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::false(Url::isAbsolute('/'));
Assert::false(Url::isAbsolute('//'));
Assert::false(Url::isAbsolute(''));
Assert::false(Url::isAbsolute('https'));
Assert::true(Url::isAbsolute('https:'));
