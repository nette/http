<?php

/**
 * Test: Nette\Http\Helpers.
 */

declare(strict_types=1);

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



test('date formatting', function () {
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate('1994-11-15T08:12:31+0000'));
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate('1994-11-15T10:12:31+0200'));
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate(new DateTime('1994-11-15T06:12:31-0200')));
	Assert::same('Tue, 15 Nov 1994 08:12:31 GMT', Helpers::formatDate(784_887_151));
});
