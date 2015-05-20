<?php

/**
 * Test: Nette\Http\Request files.
 */

use Nette\Http,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


// Setup environment
$_FILES = [
	'file1' => [
		'name' => 'readme.txt',
		'type' => 'text/plain',
		'tmp_name' => 'C:\\PHP\\temp\\php1D5B.tmp',
		'error' => 0,
		'size' => 209,
	],

	'file2' => [
		'name' => [
			2 => 'license.txt',
		],

		'type' => [
			2 => 'text/plain',
		],

		'tmp_name' => [
			2 => 'C:\\PHP\\temp\\php1D5C.tmp',
		],

		'error' => [
			2 => 0,
		],

		'size' => [
			2 => 3013,
		],
	],

	'file3' => [
		'name' => [
			'y' => [
				'z' => 'default.htm',
			],
			1 => 'logo.gif',
		],

		'type' => [
			'y' => [
				'z' => 'text/html',
			],
			1 => 'image/gif',
		],

		'tmp_name' => [
			'y' => [
				'z' => 'C:\\PHP\\temp\\php1D5D.tmp',
			],
			1 => 'C:\\PHP\\temp\\php1D5E.tmp',
		],

		'error' => [
			'y' => [
				'z' => 0,
			],
			1 => 0,
		],

		'size' => [
			'y' => [
				'z' => 26320,
			],
			1 => 3519,
		],
	],

	'empty1' => [
		'name' => '',
		'type' => '',
		'tmp_name' => '',
		'error' => UPLOAD_ERR_NO_FILE,
		'size' => 0,
	],

	'empty2' => [
		'name' => [''],
		'type' => [''],
		'tmp_name' => [''],
		'error' => [UPLOAD_ERR_NO_FILE],
		'size' => [0],
	],
];

$factory = new Http\RequestFactory;
$request = $factory->createHttpRequest();

Assert::type( 'Nette\Http\FileUpload', $request->files['file1'] );
Assert::type( 'Nette\Http\FileUpload', $request->files['file2'][2] );
Assert::type( 'Nette\Http\FileUpload', $request->files['file3']['y']['z'] );
Assert::type( 'Nette\Http\FileUpload', $request->files['file3'][1] );

Assert::false( isset($request->files['file0']) );
Assert::true( isset($request->files['file1']) );

Assert::null( $request->getFile('empty1') );
Assert::same( [NULL], $request->getFile('empty2') );
