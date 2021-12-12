<?php

/**
 * Test: Nette\Http\Request files.
 */

declare(strict_types=1);

use Nette\Http;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Setup environment
$_FILES = [
	'files' => [
		'name' => ['1a.jpg'],
		'type' => ['image/jpeg'],
		'tmp_name' => ['C:\\PHP\\temp\\php1D5D.tmp'],
		'error' => [0],
		'size' => [12345],
	],
];

$factory = new Http\RequestFactory;
$request = $factory->fromGlobals();

Assert::type('array', $request->files['files']);
Assert::count(1, $request->files['files']);
Assert::type(Nette\Http\FileUpload::class, $request->files['files'][0]);
Assert::same(12345, $request->files['files'][0]->getSize());
