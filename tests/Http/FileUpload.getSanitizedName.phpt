<?php

/**
 * Test: Nette\Http\FileUpload getSanitizedName test.
 */

declare(strict_types=1);

use Nette\Http\FileUpload;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function getSanitizedName(string $name, ?string $type = null): string
{
	$file = new FileUpload(['name' => $name, 'size' => 0, 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE]);
	Assert::with($file, function () use ($file, $type) {
		$file->type = $type;
		$file->extension = $type === null ? null : explode('/', $type)[1];
	});
	return $file->getSanitizedName();
}


test('name', function () {
	Assert::same('unknown', getSanitizedName(''));
	Assert::same('unknown', getSanitizedName('--'));
	Assert::same('foo', getSanitizedName('foo'));
	Assert::same('foo', getSanitizedName('.foo.'));
	Assert::same('readme.txt', getSanitizedName('readme.txt'));
	Assert::same('image.png', getSanitizedName('./.image.png'));
	Assert::same('image.png', getSanitizedName('../.image.png'));
	Assert::same('image.png', getSanitizedName('..\.image.png\\'));
	Assert::same('10.20.pdf', getSanitizedName('10+.+20.pdf'));
});


test('name & extension', function () {
	Assert::same('unknown', getSanitizedName('', 'application/pdf'));
	Assert::same('unknown', getSanitizedName('--', 'application/pdf'));
	Assert::same('foo', getSanitizedName('foo', 'application/pdf'));
	Assert::same('foo.jpg', getSanitizedName('foo.jpg', 'application/pdf'));
	Assert::same('foo.php', getSanitizedName('foo.php', 'application/pdf'));
	Assert::same('image.png', getSanitizedName('./.image.png', 'application/pdf'));
});


test('image name & extension', function () {
	Assert::same('unknown.jpeg', getSanitizedName('', 'image/jpeg'));
	Assert::same('unknown.jpeg', getSanitizedName('--', 'image/jpeg'));
	Assert::same('foo.jpeg', getSanitizedName('foo', 'image/jpeg'));
	Assert::same('foo.jpeg', getSanitizedName('foo.jpg', 'image/jpeg'));
	Assert::same('foo.jpeg', getSanitizedName('foo.php', 'image/jpeg'));
	Assert::same('image.jpeg', getSanitizedName('./.image.png', 'image/jpeg'));
});
