<?php

/**
 * Test: Nette\Http\Request get.
 */

use Nette\Http\RequestFactory,
    Nette\Http\FileUpload,
	Tester\Assert;

require __DIR__ . '/../bootstrap.php';


// Test for GET
$_SERVER = array(
	'REQUEST_METHOD' => 'GET',
	'REQUEST_URI' => '/file.php?x param=val.&pa%%72am=val2&quotes\\"=\\"&param3=v%20a%26l%3Du%2Be)',
);
test(function() {
    $factory = new RequestFactory;
    $request = $factory->createHttpRequest();

	Assert::same('GET', $request->getMethod());
	Assert::same('val.', $request->get('x_param'));
});

// Test for POST
$_SERVER = array(
	'REQUEST_METHOD' => 'POST'
);
$_POST = array(
    'x_param' => 'val.'
);
test(function() {
    $factory = new RequestFactory;
    $request = $factory->createHttpRequest();

	Assert::same('POST', $request->getMethod());
	Assert::same('val.', $request->get('x_param'));
});

// Test for FILES
test(function() {
    $_FILES = array(
        'file' => array(
            'name' => 'readme.txt',
            'type' => 'text/plain',
            'tmp_name' => __DIR__ . '/files/file.txt',
            'error' => 0,
            'size' => 209,
        )
    );
    $upload = new FileUpload($_FILES['file']);
    $factory = new RequestFactory;
    $request = $factory->createHttpRequest();

	Assert::equal($upload, $request->getFile('file'));
});