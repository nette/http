<?php declare(strict_types=1);

/**
 * Test: Nette\Http\IPAddress range-membership predicates.
 */

use Nette\Http\IPAddress;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('isPublic recognizes globally routable addresses', function () {
	Assert::true((new IPAddress('1.1.1.1'))->isPublic());
	Assert::true((new IPAddress('8.8.8.8'))->isPublic());
	Assert::true((new IPAddress('2001:4860:4860::8888'))->isPublic());
});


test('isPublic rejects private/loopback/link-local/reserved', function () {
	Assert::false((new IPAddress('192.168.1.1'))->isPublic());
	Assert::false((new IPAddress('10.0.0.1'))->isPublic());
	Assert::false((new IPAddress('127.0.0.1'))->isPublic());
	Assert::false((new IPAddress('169.254.169.254'))->isPublic());
	Assert::false((new IPAddress('::1'))->isPublic());
	Assert::false((new IPAddress('fe80::1'))->isPublic());
	Assert::false((new IPAddress('fc00::1'))->isPublic());
});


test('isPrivate matches RFC 1918 IPv4 ranges', function () {
	Assert::true((new IPAddress('10.0.0.1'))->isPrivate());
	Assert::true((new IPAddress('10.255.255.255'))->isPrivate());
	Assert::true((new IPAddress('172.16.0.1'))->isPrivate());
	Assert::true((new IPAddress('172.31.255.255'))->isPrivate());
	Assert::true((new IPAddress('192.168.0.1'))->isPrivate());
});


test('isPrivate rejects edges of private ranges', function () {
	Assert::false((new IPAddress('9.255.255.255'))->isPrivate());
	Assert::false((new IPAddress('11.0.0.0'))->isPrivate());
	Assert::false((new IPAddress('172.15.255.255'))->isPrivate());
	Assert::false((new IPAddress('172.32.0.0'))->isPrivate());
	Assert::false((new IPAddress('192.167.255.255'))->isPrivate());
	Assert::false((new IPAddress('192.169.0.0'))->isPrivate());
});


test('isPrivate matches RFC 4193 IPv6 ULA range', function () {
	Assert::true((new IPAddress('fc00::1'))->isPrivate());
	Assert::true((new IPAddress('fd00::1'))->isPrivate());
	Assert::false((new IPAddress('fe00::1'))->isPrivate());
});


test('isLoopback matches IPv4 and IPv6 loopback', function () {
	Assert::true((new IPAddress('127.0.0.1'))->isLoopback());
	Assert::true((new IPAddress('127.255.255.255'))->isLoopback());
	Assert::true((new IPAddress('::1'))->isLoopback());
	Assert::false((new IPAddress('128.0.0.1'))->isLoopback());
	Assert::false((new IPAddress('::2'))->isLoopback());
});


test('isLinkLocal matches IPv4 and IPv6 link-local', function () {
	Assert::true((new IPAddress('169.254.0.1'))->isLinkLocal());
	Assert::true((new IPAddress('169.254.169.254'))->isLinkLocal());
	Assert::true((new IPAddress('fe80::1'))->isLinkLocal());
	Assert::true((new IPAddress('febf::1'))->isLinkLocal());
	Assert::false((new IPAddress('169.255.0.1'))->isLinkLocal());
	Assert::false((new IPAddress('fec0::1'))->isLinkLocal());
});


test('isMulticast matches IPv4 and IPv6 multicast', function () {
	Assert::true((new IPAddress('224.0.0.1'))->isMulticast());
	Assert::true((new IPAddress('239.255.255.255'))->isMulticast());
	Assert::true((new IPAddress('ff00::1'))->isMulticast());
	Assert::true((new IPAddress('ff02::1'))->isMulticast());
	Assert::false((new IPAddress('223.255.255.255'))->isMulticast());
	Assert::false((new IPAddress('240.0.0.0'))->isMulticast());
});


test('isReserved matches CGNAT and documentation prefixes', function () {
	Assert::true((new IPAddress('100.64.0.1'))->isReserved());
	Assert::true((new IPAddress('100.127.255.255'))->isReserved());
	Assert::true((new IPAddress('192.0.2.1'))->isReserved());
	Assert::true((new IPAddress('203.0.113.1'))->isReserved());
	Assert::true((new IPAddress('2001:db8::1'))->isReserved());
});


test('IPv4-mapped IPv6 evaluates as the embedded IPv4', function () {
	Assert::true((new IPAddress('::ffff:127.0.0.1'))->isLoopback());
	Assert::true((new IPAddress('::ffff:192.168.1.1'))->isPrivate());
	Assert::true((new IPAddress('::ffff:169.254.169.254'))->isLinkLocal());
	Assert::false((new IPAddress('::ffff:8.8.8.8'))->isLoopback());
	Assert::false((new IPAddress('::ffff:8.8.8.8'))->isPrivate());
	Assert::true((new IPAddress('::ffff:8.8.8.8'))->isPublic());
});


test('predicates are mutually exclusive for non-overlapping ranges', function () {
	$publicIp = new IPAddress('1.1.1.1');
	Assert::true($publicIp->isPublic());
	Assert::false($publicIp->isPrivate());
	Assert::false($publicIp->isLoopback());
	Assert::false($publicIp->isLinkLocal());
	Assert::false($publicIp->isMulticast());
	Assert::false($publicIp->isReserved());
});
