<?php

/**
 * Test: Nette\Http\Request detectLanguage.
 */

use Nette\Http,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function() {
	$headers = array('Accept-Language' => 'en, cs');
	$request = new Http\Request(new Http\UrlScript, NULL, NULL, NULL, NULL, $headers);

	Assert::same( 'en', $request->detectLanguage(array('en', 'cs')) );
	Assert::same( 'en', $request->detectLanguage(array('cs', 'en')) );
	Assert::null( $request->detectLanguage(array('xx')) );
});


test(function() {
	$headers = array('Accept-Language' => 'da, en-gb;q=0.8, en;q=0.7');
	$request = new Http\Request(new Http\UrlScript, NULL, NULL, NULL, NULL, $headers);

	Assert::same( 'en-gb', $request->detectLanguage(array('en', 'en-gb')) );
	Assert::same( 'en', $request->detectLanguage(array('en')) );
});


test(function() {
	$headers = array();
	$request = new Http\Request(new Http\UrlScript, NULL, NULL, NULL, NULL, $headers);

	Assert::null( $request->detectLanguage(array('en')) );
});


test(function() {
	$headers = array('Accept-Language' => 'garbage');
	$request = new Http\Request(new Http\UrlScript, NULL, NULL, NULL, NULL, $headers);

	Assert::null( $request->detectLanguage(array('en')) );
});
