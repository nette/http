<?php declare(strict_types=1);

/**
 * Test: Nette\Http\IPAddress::isInRange().
 */

use Nette\Http\IPAddress;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('isInRange matches IPv4 CIDR', function () {
	$ip = new IPAddress('192.168.1.42');
	Assert::true($ip->isInRange('192.168.0.0/16'));
	Assert::true($ip->isInRange('192.168.1.0/24'));
	Assert::true($ip->isInRange('192.168.1.42/32'));
	Assert::false($ip->isInRange('192.168.2.0/24'));
	Assert::false($ip->isInRange('10.0.0.0/8'));
});


test('isInRange matches IPv6 CIDR', function () {
	$ip = new IPAddress('2001:db8::42');
	Assert::true($ip->isInRange('2001:db8::/32'));
	Assert::true($ip->isInRange('2001:db8::/64'));
	Assert::true($ip->isInRange('2001:db8::42/128'));
	Assert::false($ip->isInRange('2001:db9::/32'));
	Assert::false($ip->isInRange('::1/128'));
});


test('isInRange treats bare IP as exact match', function () {
	$ip = new IPAddress('1.2.3.4');
	Assert::true($ip->isInRange('1.2.3.4'));
	Assert::false($ip->isInRange('1.2.3.5'));
});


test('isInRange returns false for cross-family comparison', function () {
	$v4 = new IPAddress('192.168.1.1');
	$v6 = new IPAddress('2001:db8::1');
	Assert::false($v4->isInRange('::/0'));
	Assert::false($v6->isInRange('0.0.0.0/0'));
});


test('isInRange normalizes IPv4-mapped IPv6 to IPv4 ranges', function () {
	$mapped = new IPAddress('::ffff:192.168.1.1');
	Assert::true($mapped->isInRange('192.168.0.0/16'));
	Assert::true($mapped->isInRange('192.168.1.1/32'));
	Assert::false($mapped->isInRange('10.0.0.0/8'));
});


test('isInRange returns false for malformed CIDR', function () {
	$ip = new IPAddress('1.2.3.4');
	Assert::false($ip->isInRange(''));
	Assert::false($ip->isInRange('not-a-cidr'));
	Assert::false($ip->isInRange('1.2.3.4/33'));
	Assert::false($ip->isInRange('1.2.3.4/-1'));
	Assert::false($ip->isInRange('1.2.3.4/abc'));
});


test('isInRange handles boundary prefixes', function () {
	$ip = new IPAddress('1.2.3.4');
	Assert::true($ip->isInRange('0.0.0.0/0'));
	Assert::false((new IPAddress('5.6.7.8'))->isInRange('1.2.3.4/32'));
});


test('isInRange handles non-byte-aligned prefixes', function () {
	Assert::true((new IPAddress('100.64.0.1'))->isInRange('100.64.0.0/10'));
	Assert::true((new IPAddress('100.127.255.255'))->isInRange('100.64.0.0/10'));
	Assert::false((new IPAddress('100.128.0.0'))->isInRange('100.64.0.0/10'));
	Assert::false((new IPAddress('100.63.255.255'))->isInRange('100.64.0.0/10'));
});
