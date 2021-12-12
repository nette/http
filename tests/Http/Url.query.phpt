<?php

/**
 * Test: Nette\Http\Url query manipulation.
 */

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$url = new Url('http://hostname/path?arg=value');
Assert::same('arg=value', $url->query);
Assert::same(['arg' => 'value'], $url->getQueryParameters());

$url->appendQuery(null);
Assert::same('arg=value', $url->query);
Assert::same(['arg' => 'value'], $url->getQueryParameters());

$url->appendQuery([null]);
Assert::same('arg=value', $url->query);
Assert::same([null, 'arg' => 'value'], $url->getQueryParameters());

$url->appendQuery('arg2=value2');
Assert::same('arg=value&arg2=value2', $url->query);
Assert::same(['arg' => 'value', 'arg2' => 'value2'], $url->getQueryParameters());

$url->appendQuery(['arg3' => 'value3']);
Assert::same('arg3=value3&arg=value&arg2=value2', $url->query);

$url->appendQuery('arg4[]=1');
$url->appendQuery('arg4[]=2');
Assert::same('arg3=value3&arg=value&arg2=value2&arg4%5B0%5D=1&arg4%5B1%5D=2', $url->query);

$url->appendQuery('arg4[0]=3');
Assert::same('arg3=value3&arg=value&arg2=value2&arg4%5B0%5D=3&arg4%5B1%5D=2', $url->query);

$url->appendQuery(['arg4' => 4]);
Assert::same('arg4=4&arg3=value3&arg=value&arg2=value2', $url->query);


$url->setQuery(['arg3' => 'value3']);
Assert::same('arg3=value3', $url->query);
Assert::same(['arg3' => 'value3'], $url->getQueryParameters());

$url->setQuery(['arg' => 'value']);
Assert::same('value', $url->getQueryParameter('arg'));
Assert::same(null, $url->getQueryParameter('invalid'));

$url->setQueryParameter('arg2', 'abc');
Assert::same('abc', $url->getQueryParameter('arg2'));
Assert::same(['arg' => 'value', 'arg2' => 'abc'], $url->getQueryParameters());
$url->setQueryParameter('arg2', 'def');
Assert::same('def', $url->getQueryParameter('arg2'));
Assert::same(['arg' => 'value', 'arg2' => 'def'], $url->getQueryParameters());
$url->setQueryParameter('arg2', null);
Assert::same(null, $url->getQueryParameter('arg2'));
Assert::same(['arg' => 'value', 'arg2' => null], $url->getQueryParameters());


$url = new Url('http://hostname/path?arg=value');
$url->setQuery([null]);
Assert::same('http://hostname/path', $url->getAbsoluteUrl());

$url = new Url('http://hostname/path?arg=value');
$url->setQuery('');
Assert::same('http://hostname/path', $url->getAbsoluteUrl());

$url = new Url('http://hostname/path?arg=value');
$url->setQuery('a=1');
Assert::same('http://hostname/path?a=1', $url->getAbsoluteUrl());
