<?php

/**
 * Test: Nette\Http\RequestFactory body parsing.
 */

use Nette\Http\InvalidRequestBodyException;
use Nette\Http\Request;
use Nette\Http\RequestFactory;
use Nette\Utils\JsonException;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$factory = new RequestFactory;

$setRawBody = function ($rawBody) {
	$this->rawBodyCallback = function () use ($rawBody) {
		return $rawBody;
	};
};


test(function () use ($factory, $setRawBody) {
	$_SERVER = [
		'CONTENT_TYPE' => 'application/json',
	];

	$request = $factory->createHttpRequest();
	$setRawBody->bindTo($request, Request::class)->__invoke('[1, 2.0, "3", true, false, null, {}]');
	Assert::same('[1, 2.0, "3", true, false, null, {}]', $request->getRawBody());
	Assert::equal([1, 2.0, '3', TRUE, FALSE, NULL, new stdClass], $request->body);
});


test(function () use ($factory, $setRawBody) {
	$_SERVER = [
		'CONTENT_TYPE' => 'application/json',
	];

	$request = $factory->createHttpRequest();
	$setRawBody->bindTo($request, Request::class)->__invoke('[');
	Assert::same('[', $request->getRawBody());
	$e = Assert::exception([$request, 'getBody'], InvalidRequestBodyException::class);
	Assert::type(JsonException::class, $e->getPrevious());
});


test(function () use ($factory, $setRawBody) {
	$_SERVER = [
		'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
	];

	$_POST = [
		'a' => 'b',
	];

	$request = $factory->createHttpRequest();
	$setRawBody->bindTo($request, Request::class)->__invoke('a=c');
	Assert::same('a=c', $request->getRawBody());
	Assert::equal(['a' => 'b'], $request->body);
});
