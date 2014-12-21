<?php

/**
 * Test: Nette\Http\Url::parseQuery()
 */

use Nette\Http\Url,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


Assert::same( array(), Url::parseQuery('') );
Assert::same( array('key' => ''), Url::parseQuery('key') );
Assert::same( array('key' => ''), Url::parseQuery('key=') );
Assert::same( array('key' => 'val'), Url::parseQuery('key=val') );
Assert::same( array('key' => ''), Url::parseQuery('&key=&') );
Assert::same( array('a' => array('val', 'val')), Url::parseQuery('a[]=val&a[]=val') );
Assert::same( array('a' => array('x' => 'val', 'y' => 'val')), Url::parseQuery('%61[x]=val&%61[y]=val') );
Assert::same( array('a_b' => 'val', 'c' => array('d e' => 'val')), Url::parseQuery('a b=val&c[d e]=val') );
Assert::same( array('a_b' => 'val', 'c' => array('d.e' => 'val')), Url::parseQuery('a.b=val&c[d.e]=val') );
Assert::same( array('key"\'' => '"\''), Url::parseQuery('key"\'="\'') ); // magic quotes
