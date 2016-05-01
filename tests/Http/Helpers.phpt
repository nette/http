<?php

/**
 * Test: Nette\Http\Helpers.
 */

use Nette\Http\Helpers;
use Tester\Assert;


require __DIR__ . '/../bootstrap.php';


test(function () {
	Assert::true(Helpers::ipMatch('192.168.68.233', '192.168.68.233'));
	Assert::false(Helpers::ipMatch('192.168.68.234', '192.168.68.233'));
	Assert::true(Helpers::ipMatch('192.168.64.0', '192.168.68.233/20'));
	Assert::false(Helpers::ipMatch('192.168.63.255', '192.168.68.233/20'));
	Assert::true(Helpers::ipMatch('192.168.79.254', '192.168.68.233/20'));
	Assert::false(Helpers::ipMatch('192.168.80.0', '192.168.68.233/20'));
	Assert::true(Helpers::ipMatch('127.0.0.1', '192.168.68.233/0'));
	Assert::false(Helpers::ipMatch('127.0.0.1', '192.168.68.233/33'));

	Assert::false(Helpers::ipMatch('127.0.0.1', '7F00:0001::'));
});



test(function () {
	Assert::true(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '2001:db8::'));
	Assert::false(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '2002:db8::'));
	Assert::false(Helpers::ipMatch('2001:db8:0:0:0:0:0:1', '2001:db8::'));
	Assert::true(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '2001:db8::/48'));
	Assert::true(Helpers::ipMatch('2001:db8:0:ffff:ffff:ffff:ffff:ffff', '2001:db8::/48'));
	Assert::false(Helpers::ipMatch('2001:db8:1::', '2001:db8::/48'));
	Assert::false(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '2001:db8::/129'));
	Assert::false(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '32.1.13.184/32'));
});



test(function () {
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate('1994-11-15T08:12:31+0000'));
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate('1994-11-15T10:12:31+0200'));
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate(new DateTime('1994-11-15T06:12:31-0200')));
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate(784887151));
});



test(function () {
	Assert::same(
		['text/html' => NULL],
		Helpers::parseHeader('text/html')
	);

	Assert::same(
		['text/html' => NULL, 'charset' => 'iso-8859-1'],
		Helpers::parseHeader('text/html; charset="iso-8859-1"')
	);

	Assert::same(
		['text/html' => NULL, 'charset' => 'ISO-8859-4'],
		Helpers::parseHeader('text/html; charset=ISO-8859-4')
	);

	Assert::same(
		['text/html' => NULL, 'charset' => 'utf-8'],
		Helpers::parseHeader('text/html;charset=utf-8')
	);

	Assert::same(
		['INLINE' => NULL, 'FILENAME' => 'an example.html'],
		Helpers::parseHeader('INLINE; FILENAME= "an example.html"')
	);

	// SUPPORT THIS?
	Assert::same(
		['attachment' => NULL, 'filename' => '€ rates'],
		Helpers::parseHeader('attachment; filename*= UTF-8\'\'%e2%82%ac%20rates')
	);

/*

	Content-Type: token "/" token params

	params     = *( OWS ";" OWS parameter )
	param      = token "=" ( token / quoted-string )

 */
});
