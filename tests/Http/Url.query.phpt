<?php

/**
 * Test: Nette\Http\Url query manipulation.
 */

use Nette\Http\Url,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$url = new Url('http://hostname/path?arg=value');
Assert::same( 'arg=value',  $url->query );
Assert::same( array('arg' => 'value'),  $url->getQueryParameters() );

$url->appendQuery(NULL);
Assert::same( 'arg=value',  $url->query );
Assert::same( array('arg' => 'value'),  $url->getQueryParameters() );

$url->appendQuery(array(NULL));
Assert::same( 'arg=value',  $url->query );
Assert::same( array(NULL, 'arg' => 'value'),  $url->getQueryParameters() );

$url->appendQuery('arg2=value2');
Assert::same( 'arg=value&arg2=value2',  $url->query );
Assert::same( array('arg' => 'value', 'arg2' => 'value2'),  $url->getQueryParameters() );

$url->appendQuery(array('arg3' => 'value3'));
Assert::same( 'arg3=value3&arg=value&arg2=value2',  $url->query );

$url->appendQuery('arg4[]=1');
$url->appendQuery('arg4[]=2');
Assert::same( 'arg3=value3&arg=value&arg2=value2&arg4%5B0%5D=1&arg4%5B1%5D=2',  $url->query );

$url->appendQuery('arg4[0]=3');
Assert::same( 'arg3=value3&arg=value&arg2=value2&arg4%5B0%5D=3&arg4%5B1%5D=2',  $url->query );

$url->appendQuery(array('arg4' => 4));
Assert::same( 'arg4=4&arg3=value3&arg=value&arg2=value2',  $url->query );


$url->setQuery(array('arg3' => 'value3'));
Assert::same( 'arg3=value3',  $url->query );
Assert::same( array('arg3' => 'value3'),  $url->getQueryParameters() );

$url->setQuery(array('arg' => 'value'));
Assert::same( 'value', $url->getQueryParameter('arg') );
Assert::same( NULL, $url->getQueryParameter('invalid') );
Assert::same( 123, $url->getQueryParameter('invalid', 123) );

$url->setQueryParameter('arg2', 'abc');
Assert::same( 'abc', $url->getQueryParameter('arg2') );
Assert::same( array('arg' => 'value', 'arg2' => 'abc'),  $url->getQueryParameters() );
$url->setQueryParameter('arg2', 'def');
Assert::same( 'def', $url->getQueryParameter('arg2') );
Assert::same( array('arg' => 'value', 'arg2' => 'def'),  $url->getQueryParameters() );
$url->setQueryParameter('arg2', NULL);
Assert::same( NULL, $url->getQueryParameter('arg2') );
Assert::same( array('arg' => 'value', 'arg2' => NULL),  $url->getQueryParameters() );


$url = new Url('http://hostname/path?arg=value');
$url->setQuery(array(NULL));
Assert::same( 'http://hostname/path', $url->getAbsoluteUrl() );
