<?php declare(strict_types=1);

/**
 * Test: Nette\Http\IPAddress construction and form predicates.
 */

use Nette\Http\IPAddress;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('constructor accepts valid IPv4', function () {
	$ip = new IPAddress('192.168.1.1');
	Assert::same('192.168.1.1', $ip->address);
	Assert::same('192.168.1.1', (string) $ip);
});


test('constructor accepts valid IPv6', function () {
	$ip = new IPAddress('2001:db8::1');
	Assert::same('2001:db8::1', $ip->address);
});


test('constructor accepts IPv4-mapped IPv6', function () {
	$ip = new IPAddress('::ffff:192.168.1.1');
	Assert::same('::ffff:192.168.1.1', $ip->address);
});


test('constructor rejects malformed input', function () {
	Assert::exception(
		fn() => new IPAddress('not-an-ip'),
		Nette\InvalidArgumentException::class,
	);
	Assert::exception(
		fn() => new IPAddress('256.0.0.1'),
		Nette\InvalidArgumentException::class,
	);
	Assert::exception(
		fn() => new IPAddress(''),
		Nette\InvalidArgumentException::class,
	);
});


test('isValid returns true for valid IPs', function () {
	Assert::true(IPAddress::isValid('1.2.3.4'));
	Assert::true(IPAddress::isValid('::1'));
	Assert::true(IPAddress::isValid('::ffff:127.0.0.1'));
	Assert::true(IPAddress::isValid('2001:db8::'));
});


test('isValid returns false for invalid input', function () {
	Assert::false(IPAddress::isValid(''));
	Assert::false(IPAddress::isValid('not-an-ip'));
	Assert::false(IPAddress::isValid('256.0.0.1'));
	Assert::false(IPAddress::isValid('1.2.3'));
	Assert::false(IPAddress::isValid('zzzz::'));
});


test('tryFrom returns instance for valid input', function () {
	$ip = IPAddress::tryFrom('1.2.3.4');
	Assert::type(IPAddress::class, $ip);
	Assert::same('1.2.3.4', $ip->address);
});


test('tryFrom returns null for invalid input', function () {
	Assert::null(IPAddress::tryFrom('garbage'));
	Assert::null(IPAddress::tryFrom(''));
});


test('isIPv4 distinguishes pure IPv4 from IPv6 forms', function () {
	Assert::true((new IPAddress('1.2.3.4'))->isIPv4());
	Assert::false((new IPAddress('::1'))->isIPv4());
	Assert::false((new IPAddress('::ffff:1.2.3.4'))->isIPv4());
});


test('isIPv6 includes IPv4-mapped form', function () {
	Assert::true((new IPAddress('::1'))->isIPv6());
	Assert::true((new IPAddress('2001:db8::1'))->isIPv6());
	Assert::true((new IPAddress('::ffff:1.2.3.4'))->isIPv6());
	Assert::false((new IPAddress('1.2.3.4'))->isIPv6());
});


test('isIPv4Mapped detects ::ffff:a.b.c.d form only', function () {
	Assert::true((new IPAddress('::ffff:1.2.3.4'))->isIPv4Mapped());
	Assert::true((new IPAddress('::ffff:127.0.0.1'))->isIPv4Mapped());
	Assert::false((new IPAddress('1.2.3.4'))->isIPv4Mapped());
	Assert::false((new IPAddress('::1'))->isIPv4Mapped());
	Assert::false((new IPAddress('2001:db8::1'))->isIPv4Mapped());
});


test('toIPv4 converts mapped form', function () {
	Assert::same('1.2.3.4', (string) (new IPAddress('::ffff:1.2.3.4'))->toIPv4());
	Assert::same('127.0.0.1', (string) (new IPAddress('::ffff:127.0.0.1'))->toIPv4());
});


test('toIPv4 returns same instance for non-mapped', function () {
	$pure = new IPAddress('1.2.3.4');
	Assert::same('1.2.3.4', (string) $pure->toIPv4());

	$v6 = new IPAddress('2001:db8::1');
	Assert::same('2001:db8::1', (string) $v6->toIPv4());
});


test('__toString preserves original without normalization', function () {
	Assert::same('192.168.1.1', (string) new IPAddress('192.168.1.1'));
	Assert::same('2001:DB8::1', (string) new IPAddress('2001:DB8::1'));
});
