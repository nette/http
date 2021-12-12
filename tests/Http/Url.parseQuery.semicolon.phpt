<?php

/**
 * Test: Nette\Http\Url::parseQuery()
 * @phpIni arg_separator.input=&;
 */

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::same([], Url::parseQuery(''));
Assert::same(['key' => ''], Url::parseQuery('key'));
Assert::same(['key' => ''], Url::parseQuery('key='));
Assert::same(['key' => 'val'], Url::parseQuery('key=val'));
Assert::same(['key' => 'val', 'val' => ''], Url::parseQuery('key=val&val'));
Assert::same(['key' => ''], Url::parseQuery(';key=;'));
Assert::same(['a' => ['val', 'val']], Url::parseQuery('a[]=val;a[]=val'));
Assert::same(['a' => ['x' => 'val', 'y' => 'val']], Url::parseQuery('%61[x]=val;%61[y]=val'));
Assert::same(['a b' => 'val', 'c' => ['d e' => 'val']], Url::parseQuery('a b=val;c[d e]=val'));
Assert::same(['a.b' => 'val', 'c' => ['d.e' => 'val']], Url::parseQuery('a.b=val;c[d.e]=val'));
Assert::same(['key"\'' => '"\''], Url::parseQuery('key"\'="\'')); // magic quotes
Assert::same([], Url::parseQuery('%00')); // null
