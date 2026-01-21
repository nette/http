<?php declare(strict_types=1);

/**
 * PHPStan type tests for Http.
 * Run: vendor/bin/phpstan analyse tests/types
 */

use Nette\Http\FileUpload;
use Nette\Http\SessionSection;
use function PHPStan\Testing\assertType;


function testSessionSectionIterator(SessionSection $section): void
{
	foreach ($section as $key => $value) {
		assertType('string', $key);
		assertType('mixed', $value);
	}
}


function testSessionSectionArrayAccess(SessionSection $section): void
{
	$section->remove();
	$value = $section['key'];
	assertType('mixed', $value);
}


function testFileUploadGetImageSize(FileUpload $upload): void
{
	$size = $upload->getImageSize();
	assertType('array{int, int}|null', $size);
}
