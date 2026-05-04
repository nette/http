<?php declare(strict_types=1);

/**
 * Test: Nette\Http\UrlValidator.
 */

use Nette\Http\UrlImmutable;
use Nette\Http\UrlValidator;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('default policy: https + port 443 + public IPs only', function () {
	$validator = new UrlValidator;
	Assert::true($validator->allowsWithoutDns('https://example.com/path'));
	Assert::false($validator->allowsWithoutDns('http://example.com/'));
	Assert::false($validator->allowsWithoutDns('https://example.com:8080/'));
	Assert::false($validator->allowsWithoutDns('ftp://example.com/'));
});


test('null and malformed URL always rejected', function () {
	$validator = new UrlValidator;
	Assert::false($validator->allows(null));
	Assert::false($validator->allowsWithoutDns(null));
	Assert::false($validator->allowsWithoutDns('not a url'));
	Assert::false($validator->allowsWithoutDns(''));
});


test('schemes policy', function () {
	$bothProtocols = new UrlValidator(schemes: ['http', 'https'], ports: [80, 443]);
	Assert::true($bothProtocols->allowsWithoutDns('http://example.com/'));
	Assert::true($bothProtocols->allowsWithoutDns('https://example.com/'));
	Assert::false($bothProtocols->allowsWithoutDns('ftp://example.com/'));

	$noSchemes = new UrlValidator(schemes: []);
	Assert::false($noSchemes->allowsWithoutDns('https://example.com/'));
});


test('ports policy: explicit and scheme-implied', function () {
	$strict = new UrlValidator(ports: [443]);
	Assert::true($strict->allowsWithoutDns('https://example.com/'));
	Assert::true($strict->allowsWithoutDns('https://example.com:443/'));
	Assert::false($strict->allowsWithoutDns('https://example.com:8443/'));

	$multi = new UrlValidator(schemes: ['http', 'https'], ports: [80, 443, 8443]);
	Assert::true($multi->allowsWithoutDns('http://example.com/'));
	Assert::true($multi->allowsWithoutDns('https://example.com:8443/'));
	Assert::false($multi->allowsWithoutDns('https://example.com:9000/'));

	$any = new UrlValidator(ports: null);
	Assert::true($any->allowsWithoutDns('https://example.com:9999/'));
});


test('userinfo policy rejects user, user:pass, and :pass alone', function () {
	$strict = new UrlValidator;
	Assert::false($strict->allowsWithoutDns('https://user:pass@example.com/'));
	Assert::false($strict->allowsWithoutDns('https://user@example.com/'));
	Assert::false($strict->allowsWithoutDns('https://:pass@example.com/'));

	$lenient = new UrlValidator(allowUserinfo: true);
	Assert::true($lenient->allowsWithoutDns('https://user:pass@example.com/'));
	Assert::true($lenient->allowsWithoutDns('https://:pass@example.com/'));
});


test('hostAllowlist exact match', function () {
	$validator = new UrlValidator(hostAllowlist: ['api.example.com', 'cdn.example.com']);
	Assert::true($validator->allowsWithoutDns('https://api.example.com/v1/'));
	Assert::true($validator->allowsWithoutDns('https://cdn.example.com/asset.js'));
	Assert::false($validator->allowsWithoutDns('https://other.example.com/'));
	Assert::false($validator->allowsWithoutDns('https://example.com/'));
});


test('hostAllowlist wildcard subdomain matches any depth but not apex', function () {
	$validator = new UrlValidator(hostAllowlist: ['*.example.com']);
	Assert::true($validator->allowsWithoutDns('https://api.example.com/'));
	Assert::true($validator->allowsWithoutDns('https://foo.bar.example.com/'));
	Assert::false($validator->allowsWithoutDns('https://example.com/'));
	Assert::false($validator->allowsWithoutDns('https://other.com/'));
});


test('hostAllowlist apex requires explicit entry', function () {
	$validator = new UrlValidator(hostAllowlist: ['example.com', '*.example.com']);
	Assert::true($validator->allowsWithoutDns('https://example.com/'));
	Assert::true($validator->allowsWithoutDns('https://api.example.com/'));
});


test('hostBlocklist rejects matching hosts', function () {
	$validator = new UrlValidator(hostBlocklist: ['blocked.example.com', '*.evil.com']);
	Assert::false($validator->allowsWithoutDns('https://blocked.example.com/'));
	Assert::false($validator->allowsWithoutDns('https://sub.evil.com/'));
	Assert::true($validator->allowsWithoutDns('https://safe.example.com/'));
});


test('host allow- and blocklist combine: pass allow AND not match block', function () {
	$validator = new UrlValidator(
		hostAllowlist: ['*.example.com'],
		hostBlocklist: ['untrusted.example.com'],
	);
	Assert::true($validator->allowsWithoutDns('https://api.example.com/'));
	Assert::false($validator->allowsWithoutDns('https://untrusted.example.com/'));
	Assert::false($validator->allowsWithoutDns('https://other.com/'));
});


test('empty hostAllowlist rejects everything', function () {
	$validator = new UrlValidator(hostAllowlist: []);
	Assert::false($validator->allowsWithoutDns('https://example.com/'));
});


test('host matching is case-insensitive', function () {
	$validator = new UrlValidator(hostAllowlist: ['Example.COM', '*.example.com']);
	Assert::true($validator->allowsWithoutDns('https://EXAMPLE.com/'));
	Assert::true($validator->allowsWithoutDns('https://API.Example.com/'));
});


test('IP-literal host: public IPv4 passes default policy', function () {
	$validator = new UrlValidator;
	Assert::true($validator->allows('https://1.1.1.1/'));
	Assert::same(['1.1.1.1'], $validator->getResolvedIPs('https://1.1.1.1/'));
});


test('IP-literal host: private IP rejected by default', function () {
	$validator = new UrlValidator;
	Assert::false($validator->allows('https://192.168.1.1/'));
	Assert::false($validator->allows('https://10.0.0.1/'));
	Assert::same([], $validator->getResolvedIPs('https://192.168.1.1/'));
});


test('IP-literal host: loopback rejected by default', function () {
	$validator = new UrlValidator;
	Assert::false($validator->allows('https://127.0.0.1/'));
	Assert::false($validator->allows('https://[::1]/'));
});


test('IP-literal host: cloud metadata rejected by default', function () {
	$validator = new UrlValidator;
	Assert::false($validator->allows('https://169.254.169.254/'));
});


test('IP-literal host: IPv4-mapped IPv6 loopback rejected', function () {
	$validator = new UrlValidator;
	Assert::false($validator->allows('https://[::ffff:127.0.0.1]/'));
});


test('opt-in allowPrivateIps lets RFC 1918 through', function () {
	$validator = new UrlValidator(allowPrivateIps: true);
	Assert::true($validator->allows('https://192.168.1.1/'));
	Assert::true($validator->allows('https://10.0.0.5/'));
	Assert::false($validator->allows('https://127.0.0.1/'));
});


test('opt-in allowLoopback lets 127/8 and ::1 through', function () {
	$validator = new UrlValidator(allowLoopback: true);
	Assert::true($validator->allows('https://127.0.0.1/'));
	Assert::true($validator->allows('https://[::1]/'));
	Assert::false($validator->allows('https://192.168.1.1/'));
});


test('opt-in allowLinkLocal lets cloud metadata through', function () {
	$validator = new UrlValidator(allowLinkLocal: true);
	Assert::true($validator->allows('https://169.254.169.254/'));
	Assert::true($validator->allows('https://[fe80::1]/'));
	Assert::false($validator->allows('https://192.168.1.1/'));
});


test('opt-in allowReserved lets CGNAT and documentation prefixes through', function () {
	$validator = new UrlValidator(allowReserved: true);
	Assert::true($validator->allows('https://100.64.0.1/'));
	Assert::true($validator->allows('https://192.0.2.1/'));
	Assert::true($validator->allows('https://[2001:db8::1]/'));
});


test('multicast addresses always rejected even with all opt-ins', function () {
	$validator = new UrlValidator(
		allowPrivateIps: true,
		allowLoopback: true,
		allowLinkLocal: true,
		allowReserved: true,
	);
	Assert::false($validator->allows('https://224.0.0.1/'));
	Assert::false($validator->allows('https://[ff02::1]/'));
});


test('accepts UrlImmutable as input', function () {
	$validator = new UrlValidator;
	$url = new UrlImmutable('https://1.1.1.1/path');
	Assert::true($validator->allows($url));
	Assert::true($validator->allowsWithoutDns($url));
	Assert::same(['1.1.1.1'], $validator->getResolvedIPs($url));
});


test('getResolvedIPs returns empty array on policy violation', function () {
	$validator = new UrlValidator;
	Assert::same([], $validator->getResolvedIPs(null));
	Assert::same([], $validator->getResolvedIPs('http://1.1.1.1/'));
	Assert::same([], $validator->getResolvedIPs('https://192.168.1.1/'));
});
