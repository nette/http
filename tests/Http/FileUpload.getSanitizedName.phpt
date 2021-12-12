<?php

/**
 * Test: Nette\Http\FileUpload getSanitizedName test.
 */

declare(strict_types=1);

use Nette\Http\FileUpload;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


Assert::with(new FileUpload([]), function () {
	$this->name = '';
	Assert::same('unknown', $this->getSanitizedName());

	$this->name = '--';
	Assert::same('unknown', $this->getSanitizedName());

	$this->name = 'foo';
	Assert::same('foo', $this->getSanitizedName());

	$this->name = '.foo.';
	Assert::same('foo', $this->getSanitizedName());

	$this->name = 'readme.txt';
	Assert::same('readme.txt', $this->getSanitizedName());

	$this->name = './.image.png';
	Assert::same('image.png', $this->getSanitizedName());

	$this->name = '../.image.png';
	Assert::same('image.png', $this->getSanitizedName());

	$this->name = '..\.image.png\\';
	Assert::same('image.png', $this->getSanitizedName());

	$this->name = '10+.+20.pdf';
	Assert::same('10.20.pdf', $this->getSanitizedName());
});


Assert::with(new FileUpload([]), function () {
	$this->type = 'image/jpeg';

	$this->name = '';
	Assert::same('unknown.jpeg', $this->getSanitizedName());

	$this->name = '--';
	Assert::same('unknown.jpeg', $this->getSanitizedName());

	$this->name = 'foo';
	Assert::same('foo.jpeg', $this->getSanitizedName());

	$this->name = 'foo.jpg';
	Assert::same('foo.jpeg', $this->getSanitizedName());

	$this->name = 'foo.php';
	Assert::same('foo.jpeg', $this->getSanitizedName());

	$this->name = './.image.png';
	Assert::same('image.jpeg', $this->getSanitizedName());
});
