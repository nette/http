<?php

/**
 * Test: Nette\Http\RequestFactory scriptPath detection.
 */

declare(strict_types=1);

use Nette\Http\RequestFactory;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$factory = new RequestFactory;

test('script path detection from REQUEST_URI and SCRIPT_NAME', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/projects/modules-usage/www/',
		'SCRIPT_NAME' => '/projects/modules-usage/www/index.php',
	];

	Assert::same('/projects/modules-usage/www/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('URL-encoded path normalization in script path', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/projects/modules-usage/%77%77%77/',
		'SCRIPT_NAME' => '/projects/modules-usage/www/index.php',
	];

	Assert::same('/projects/modules-usage/www/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('nested path detection with script in subdirectory', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/projects/modules-usage/www/default/add-item',
		'SCRIPT_NAME' => '/projects/modules-usage/www/index.php',
	];

	Assert::same('/projects/modules-usage/www/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('script path when REQUEST_URI matches SCRIPT_NAME exactly', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/www/index.php',
		'SCRIPT_NAME' => '/www/index.php',
	];

	Assert::same('/www/index.php', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('directory-like SCRIPT_NAME handling', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/www/',
		'SCRIPT_NAME' => '/www/',
	];

	Assert::same('/www/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('path truncation to script directory', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/test/in',
		'SCRIPT_NAME' => '/test/index.php',
	];

	Assert::same('/test/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('double slash normalization in script path', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/test//',
		'SCRIPT_NAME' => '/test/index.php',
	];

	Assert::same('/test/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('exact match of REQUEST_URI and SCRIPT_NAME as directory', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/sign/in/',
		'SCRIPT_NAME' => '/sign/in/',
	];

	Assert::same('/sign/in/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('mismatched directory levels between URI and script', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/configuration/',
		'SCRIPT_NAME' => '/configuration/www/index.php',
	];

	Assert::same('/configuration/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('case sensitivity in script path components', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/blog/WWW/',
		'SCRIPT_NAME' => '/blog/www/index.php',
	];

	Assert::same('/blog/WWW/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('Windows-style SCRIPT_NAME handling', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/',
		'SCRIPT_NAME' => 'c:\index.php',
	];

	Assert::same('/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('missing REQUEST_URI fallback', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => null,
		'SCRIPT_NAME' => 'c:\index.php',
	];

	Assert::same('/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('missing SCRIPT_NAME fallback', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/',
		'SCRIPT_NAME' => null,
	];

	Assert::same('/', $factory->fromGlobals()->getUrl()->getScriptPath());
});


test('complete absence of URI and script data', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => null,
		'SCRIPT_NAME' => null,
	];

	Assert::same('/', $factory->fromGlobals()->getUrl()->getScriptPath());
});

test('root script path detection with deep URI', function () use ($factory) {
	$_SERVER = [
		'REQUEST_URI' => '/documents/show/5474',
		'SCRIPT_NAME' => '/index.php',
	];

	Assert::same('/', $factory->fromGlobals()->getUrl()->getScriptPath());
});
