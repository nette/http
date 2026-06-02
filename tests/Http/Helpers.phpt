<?php declare(strict_types=1);

/**
 * Test: Nette\Http\Helpers.
 */

use Nette\Http\Helpers;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('IPv4 address matching', function () {
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



test('IPv6 address matching', function () {
	Assert::true(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '2001:db8::'));
	Assert::false(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '2002:db8::'));
	Assert::false(Helpers::ipMatch('2001:db8:0:0:0:0:0:1', '2001:db8::'));
	Assert::true(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '2001:db8::/48'));
	Assert::true(Helpers::ipMatch('2001:db8:0:ffff:ffff:ffff:ffff:ffff', '2001:db8::/48'));
	Assert::false(Helpers::ipMatch('2001:db8:1::', '2001:db8::/48'));
	Assert::false(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '2001:db8::/129'));
	Assert::false(Helpers::ipMatch('2001:db8:0:0:0:0:0:0', '32.1.13.184/32'));
});



test('parseQualityList', function () {
	Assert::same([], Helpers::parseQualityList(''));
	Assert::same(['gzip' => 1.0], Helpers::parseQualityList('gzip'));

	// ordered by descending q-factor, default q is 1.0
	Assert::same(
		['da' => 1.0, 'en-gb' => 0.8, 'en' => 0.7],
		Helpers::parseQualityList('da, en-gb;q=0.8, en;q=0.7'),
	);

	// equal q keeps the header order (stable sort)
	Assert::same(['en' => 1.0, 'cs' => 1.0], Helpers::parseQualityList('en, cs'));

	// tokens are lowercased and trimmed, q=0 is dropped
	Assert::same(
		['text/html' => 0.9, '*/*' => 0.8],
		Helpers::parseQualityList('TEXT/HTML ; q=0.9 , identity;q=0 , */* ;q=0.8'),
	);

	// a repeated token keeps its highest q
	Assert::same(['de' => 0.9], Helpers::parseQualityList('de;q=0.9, de;q=0.1'));

	// q is capped at 1
	Assert::same(['a' => 1.0, 'b' => 0.9], Helpers::parseQualityList('a;q=5, b;q=0.9'));
});


test('date formatting', function () {
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate('1994-11-15T08:12:31+0000'));
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate('1994-11-15T10:12:31+0200'));
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate(new DateTime('1994-11-15T06:12:31-0200')));
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate(784_887_151));
});
