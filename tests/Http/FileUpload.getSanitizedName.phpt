<?php

/**
 * Test: Nette\Http\FileUpload getSanitizedName test.
 */

declare(strict_types=1);

use Nette\Http\FileUpload;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


function getSanitizedName(string $name, ?string $ext = null): string
{
	$file = new FileUpload(['name' => $name, 'size' => 0, 'tmp_name' => '', 'error' => UPLOAD_ERR_NO_FILE]);
	Assert::with($file, fn() => $file->extension = $ext);
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
	Assert::same('unknown.jpeg', getSanitizedName('', 'jpeg'));
	Assert::same('unknown.jpeg', getSanitizedName('--', 'jpeg'));
	Assert::same('foo.jpeg', getSanitizedName('foo', 'jpeg'));
	Assert::same('foo.jpeg', getSanitizedName('foo.jpg', 'jpeg'));
	Assert::same('foo.jpeg', getSanitizedName('foo.php', 'jpeg'));
	Assert::same('image.jpeg', getSanitizedName('./.image.png', 'jpeg'));
});
