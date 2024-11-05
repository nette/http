<?php

declare(strict_types=1);

use Nette\Http\UrlImmutable;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$tests = [
	'https://example.com/path/' => [
		// absolute URLs with various schemes
		'a:' => 'a:',
		'a:b' => 'a:b',
		'http://other.com/test' => 'http://other.com/test',
		'https://other.com/test' => 'https://other.com/test',
		'ftp://other.com/test' => 'ftp://other.com/test',

		// protocol-relative URLs - keep current scheme
		'//other.com/test' => 'https://other.com/test',

		// root-relative paths
		'/test' => 'https://example.com/test',
		'/test/' => 'https://example.com/test/',

		// relative paths
		'sibling' => 'https://example.com/path/sibling',
		'../parent' => 'https://example.com/parent',
		'child/' => 'https://example.com/path/child/',
	],

	// base dir with query string and fragment
	'https://example.com/path/?q=123#frag' => [
		'' => 'https://example.com/path/?q=123',
		'file' => 'https://example.com/path/file',
		'./file' => 'https://example.com/path/file',
		'/root' => 'https://example.com/root',
		'subdir/?q=456' => 'https://example.com/path/subdir/?q=456',
		'subdir/#frag' => 'https://example.com/path/subdir/#frag',
		'../file' => 'https://example.com/file',
		'/../file' => 'https://example.com/file',
		'file?newq=/..#newfrag/..' => 'https://example.com/path/file?newq=%2F..#newfrag/..',
		'?newq=/..' => 'https://example.com/path/?newq=%2F..',
		'#newfrag/..' => 'https://example.com/path/?q=123#newfrag/..',
	],

	// base file with query string and fragment
	'https://example.com/path/file?q=123#frag' => [
		'' => 'https://example.com/path/file?q=123',
		'file' => 'https://example.com/path/file',
		'./file' => 'https://example.com/path/file',
		'/root' => 'https://example.com/root',
		'subdir/file?q=123' => 'https://example.com/path/subdir/file?q=123',
		'subdir/file#frag' => 'https://example.com/path/subdir/file#frag',
		'../file' => 'https://example.com/file',
		'/../file' => 'https://example.com/file',
		'?newq=/..' => 'https://example.com/path/file?newq=%2F..',
		'#newfrag/..' => 'https://example.com/path/file?q=123#newfrag/..',
	],
];


foreach ($tests as $base => $paths) {
	$url = new UrlImmutable($base);
	foreach ($paths as $path => $expected) {
		Assert::same($expected, (string) $url->resolve($path), "Base: $base, Reference: $path");
	}
}
