<?php

/**
 * Test: Nette\Http\FileUpload basic test.
 */

declare(strict_types=1);

use Nette\Http\FileUpload;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('basic upload properties', function () {
	$upload = new FileUpload([
		'name' => 'readme.txt',
		'full_path' => 'path/to/readme.txt',
		'type' => 'text/plain',
		'tmp_name' => __DIR__ . '/files/file.txt',
		'error' => 0,
		'size' => 209,
	]);

	Assert::same('readme.txt', $upload->getName());
	Assert::same('readme.txt', $upload->getUntrustedName());
	Assert::same('readme.txt', $upload->getSanitizedName());
	Assert::same('path/to/readme.txt', $upload->getUntrustedFullPath());
	Assert::same(209, $upload->getSize());
	Assert::same(__DIR__ . '/files/file.txt', $upload->getTemporaryFile());
	Assert::same(__DIR__ . '/files/file.txt', (string) $upload);
	Assert::same(0, $upload->getError());
	Assert::true($upload->isOk());
	Assert::true($upload->hasFile());
	Assert::false($upload->isImage());
	Assert::null($upload->getSuggestedExtension());
	Assert::same(file_get_contents(__DIR__ . '/files/file.txt'), $upload->getContents());
});


test('sanitizing name with image detection', function () {
	$upload = new FileUpload([
		'name' => '../.image.png',
		'type' => 'text/plain',
		'tmp_name' => __DIR__ . '/files/logo.png',
		'error' => 0,
		'size' => 209,
	]);

	Assert::same('../.image.png', $upload->getName());
	Assert::same('image.png', $upload->getSanitizedName());
	Assert::same('../.image.png', $upload->getUntrustedFullPath());
	Assert::same('image/png', $upload->getContentType());
	Assert::same('png', $upload->getSuggestedExtension());
	Assert::same([108, 46], $upload->getImageSize());
	Assert::true($upload->isImage());
});


test('no file uploaded', function () {
	$upload = new FileUpload([
		'name' => '',
		'type' => '',
		'tmp_name' => '',
		'error' => UPLOAD_ERR_NO_FILE,
		'size' => 0,
	]);

	Assert::false($upload->isOk());
	Assert::false($upload->hasFile());
	Assert::null($upload->getContentType());
	Assert::false($upload->isImage());
	Assert::null($upload->getSuggestedExtension());
	Assert::same('', (string) $upload);
});


test('empty upload data', function () {
	$upload = new FileUpload([]);

	Assert::false($upload->isOk());
	Assert::false($upload->hasFile());
	Assert::null($upload->getContentType());
	Assert::false($upload->isImage());
	Assert::null($upload->getSuggestedExtension());
	Assert::same('', (string) $upload);
});


test('upload from file path', function () {
	$upload = new FileUpload($file = __DIR__ . '/files/file.txt');

	Assert::same('file.txt', $upload->getName());
	Assert::same('file.txt', $upload->getUntrustedName());
	Assert::same('file.txt', $upload->getSanitizedName());
	Assert::same($file, $upload->getUntrustedFullPath());
	Assert::same(filesize($file), $upload->getSize());
	Assert::same($file, $upload->getTemporaryFile());
	Assert::same($file, (string) $upload);
	Assert::same(0, $upload->getError());
	Assert::true($upload->isOk());
	Assert::true($upload->hasFile());
	Assert::false($upload->isImage());
	Assert::null($upload->getSuggestedExtension());
	Assert::same(file_get_contents($file), $upload->getContents());
});
