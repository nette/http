<?php

/**
 * Test: Nette\Http\FileUpload basic test.
 */

use Nette\Http\FileUpload;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	$upload = new FileUpload([
		'name' => 'readme.txt',
		'type' => 'text/plain',
		'tmp_name' => __DIR__ . '/files/file.txt',
		'error' => 0,
		'size' => 209,
	]);

	Assert::same('readme.txt', $upload->getName());
	Assert::same('readme.txt', $upload->getSanitizedName());
	Assert::same(209, $upload->getSize());
	Assert::same(__DIR__ . '/files/file.txt', $upload->getTemporaryFile());
	Assert::same(__DIR__ . '/files/file.txt', (string) $upload);
	Assert::same(0, $upload->getError());
	Assert::true($upload->isOk());
	Assert::true($upload->hasFile());
	Assert::false($upload->isImage());
	Assert::same(file_get_contents(__DIR__ . '/files/file.txt'), $upload->getContents());
});


test(function () {
	$upload = new FileUpload([
		'name' => '../.image.png',
		'type' => 'text/plain',
		'tmp_name' => __DIR__ . '/files/logo.png',
		'error' => 0,
		'size' => 209,
	]);

	Assert::same('../.image.png', $upload->getName());
	Assert::same('image.png', $upload->getSanitizedName());
	Assert::same('image/png', $upload->getContentType());
	Assert::true($upload->isImage());
});


test(function () {
	$upload = new FileUpload([
		'name' => '',
		'type' => '',
		'tmp_name' => '',
		'error' => UPLOAD_ERR_NO_FILE,
		'size' => 0,
	]);

	Assert::false($upload->isOk());
	Assert::false($upload->hasFile());
	Assert::false($upload->isImage());
});
