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
		'name' => ['a.jpg', 'c.jpg'],
		'type' => ['image/jpeg', 'image/jpeg'],
		'full_path' => ['a.jpg', 'b/c.jpg'],
		'tmp_name' => ['C:\\PHP\\temp\\php1D5D.tmp', 'C:\\PHP\\temp\\php1D5E.tmp'],
		'error' => [0, 0],
		'size' => [12345, 54321],
	],
];

$factory = new Http\RequestFactory;
$request = $factory->fromGlobals();

Assert::type('array', $request->files['files']);
Assert::count(2, $request->files['files']);
Assert::type(Nette\Http\FileUpload::class, $request->files['files'][0]);
Assert::type(Nette\Http\FileUpload::class, $request->files['files'][1]);

Assert::same('a.jpg', $request->files['files'][0]->getUntrustedFullPath());
Assert::same('b/c.jpg', $request->files['files'][1]->getUntrustedFullPath());
