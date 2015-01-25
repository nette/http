<?php

/**
 * Test: Nette\Http\Session flash sections.
 */

use Nette\Http\Session,
	Nette\Http\SessionSection,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function() {
	$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);

	Assert::null( $session->getFlashId() );

	$section = $session->getFlashSection('f');
	Assert::type( 'Nette\Http\SessionSection', $section );

	Assert::match( '%a%', $session->getFlashId() );
});


test(function() {
	$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript('?_fid=123')), new Nette\Http\Response);
	Assert::same( '123', $session->getFlashId() );
});


test(function() {
	$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript('?_fid[]=123')), new Nette\Http\Response);
	Assert::null( $session->getFlashId() );
});
