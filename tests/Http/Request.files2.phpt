<?php

/**
 * Test: Nette\Http\Request files.
 */

use Nette\Http;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// Setup environment
$_FILES = array(
	'files' => array(
		'name' => array('1a.jpg'),
		'type' => array('image/jpeg'),
		'tmp_name' => array('C:\\PHP\\temp\\php1D5D.tmp'),
		'error' => array(0),
		'size' => array(12345),
	),
);

$factory = new Http\RequestFactory;
$request = $factory->createHttpRequest();

Assert::type('array', $request->files['files']);
Assert::count(1, $request->files['files']);
Assert::type('Nette\Http\FileUpload', $request->files['files'][0]);
Assert::same(12345, $request->files['files'][0]->getSize());
