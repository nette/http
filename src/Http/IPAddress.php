<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use Nette;
use function chr, ctype_digit, explode, filter_var, inet_ntop, inet_pton, intdiv, str_contains, str_repeat, strlen, strncmp, substr;
use const FILTER_FLAG_IPV4, FILTER_FLAG_IPV6, FILTER_VALIDATE_IP;


/**
 * Immutable IPv4/IPv6 address with predicates for range membership and address class.
 */
final class IPAddress implements \Stringable
{
	private const PrivateRanges = [
		'10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', 'fc00::/7',
	];

	private const LoopbackRanges = ['127.0.0.0/8', '::1/128'];

	private const LinkLocalRanges = ['169.254.0.0/16', 'fe80::/10'];

	private const MulticastRanges = ['224.0.0.0/4', 'ff00::/8'];

	private const ReservedRanges = [
		'0.0.0.0/8', '100.64.0.0/10', '192.0.0.0/24', '192.0.2.0/24', '198.18.0.0/15',
		'198.51.100.0/24', '203.0.113.0/24', '240.0.0.0/4', '255.255.255.255/32',
		'::/128', '64:ff9b::/96', '100::/64', '2001::/23', '2001:db8::/32',
	];

	private string $binary;


	/**
	 * @throws Nette\InvalidArgumentException  when address is not a valid IPv4 or IPv6 address
	 */
	public function __construct(
		public readonly string $address,
	) {
		$bin = @inet_pton($address);
		if ($bin === false) {
			throw new Nette\InvalidArgumentException("Invalid IP address: $address");
		}
		$this->binary = $bin;
	}


	/**
	 * Returns true for any syntactically valid IPv4 or IPv6 address,
	 * including IPv4-mapped IPv6 (::ffff:1.2.3.4).
	 */
	public static function isValid(string $address): bool
	{
		return @inet_pton($address) !== false;
	}


	/**
	 * Returns an instance for valid input, null otherwise.
	 */
	public static function tryFrom(string $address): ?self
	{
		return self::isValid($address) ? new self($address) : null;
	}


	/**
	 * Returns true for IPv4 dotted-quad form. IPv4-mapped IPv6 returns false; see isIPv4Mapped().
	 */
	public function isIPv4(): bool
	{
		return (bool) filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
	}


	/**
	 * Returns true for IPv6 form, including IPv4-mapped (::ffff:1.2.3.4).
	 */
	public function isIPv6(): bool
	{
		return (bool) filter_var($this->address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
	}


	/**
	 * Returns true for IPv4-mapped IPv6 (::ffff:a.b.c.d). Range predicates below
	 * normalize these — ::ffff:127.0.0.1 evaluates as loopback.
	 */
	public function isIPv4Mapped(): bool
	{
		return strlen($this->binary) === 16
			&& substr($this->binary, 0, 10) === str_repeat("\0", 10)
			&& substr($this->binary, 10, 2) === "\xff\xff";
	}


	/**
	 * Converts IPv4-mapped IPv6 to IPv4 dotted-quad form. Returns $this for non-mapped.
	 */
	public function toIPv4(): self
	{
		if (!$this->isIPv4Mapped()) {
			return $this;
		}
		$ipv4 = inet_ntop(substr($this->binary, 12));
		return $ipv4 === false ? $this : new self($ipv4);
	}


	/**
	 * Tests whether this IP falls within the given CIDR block.
	 *
	 * Accepts '192.168.1.0/24' (network with prefix) or '192.168.1.1' (exact match,
	 * implicit /32 for IPv4 or /128 for IPv6). Returns false for malformed input
	 * or different IP family. IPv4-mapped IPv6 is normalized to IPv4 when the
	 * range is in IPv4 form.
	 */
	public function isInRange(string $cidr): bool
	{
		if (str_contains($cidr, '/')) {
			[$network, $prefixStr] = explode('/', $cidr, 2);
			if (!ctype_digit($prefixStr)) {
				return false;
			}
			$prefix = (int) $prefixStr;
		} else {
			$network = $cidr;
			$prefix = null;
		}

		$networkBin = @inet_pton($network);
		if ($networkBin === false) {
			return false;
		}

		// IPv4-mapped IPv6 self matches IPv4 ranges (RFC 4291 § 2.5.5.2)
		$selfBin = strlen($networkBin) === 4 && $this->isIPv4Mapped()
			? substr($this->binary, 12)
			: $this->binary;
		if (strlen($networkBin) !== strlen($selfBin)) {
			return false;
		}

		$maxBits = strlen($networkBin) * 8;
		$prefix ??= $maxBits;
		if ($prefix < 0 || $prefix > $maxBits) {
			return false;
		}

		$fullBytes = intdiv($prefix, 8);
		if (strncmp($selfBin, $networkBin, $fullBytes) !== 0) {
			return false;
		}
		$remainBits = $prefix % 8;
		if ($remainBits === 0) {
			return true;
		}
		$mask = chr((0xFF << (8 - $remainBits)) & 0xFF);
		return ($selfBin[$fullBytes] & $mask) === ($networkBin[$fullBytes] & $mask);
	}


	/**
	 * Returns true if address is publicly routable (not in any private, loopback,
	 * link-local, multicast or reserved range).
	 */
	public function isPublic(): bool
	{
		return !$this->isPrivate()
			&& !$this->isLoopback()
			&& !$this->isLinkLocal()
			&& !$this->isMulticast()
			&& !$this->isReserved();
	}


	/**
	 * Tests RFC 1918 / RFC 4193 private ranges (10/8, 172.16/12, 192.168/16, fc00::/7).
	 */
	public function isPrivate(): bool
	{
		return $this->matchesAny(self::PrivateRanges);
	}


	/**
	 * Tests loopback ranges (127.0.0.0/8, ::1/128).
	 */
	public function isLoopback(): bool
	{
		return $this->matchesAny(self::LoopbackRanges);
	}


	/**
	 * Tests link-local ranges (169.254.0.0/16 incl. cloud metadata 169.254.169.254, fe80::/10).
	 */
	public function isLinkLocal(): bool
	{
		return $this->matchesAny(self::LinkLocalRanges);
	}


	/**
	 * Tests multicast ranges (224.0.0.0/4, ff00::/8).
	 */
	public function isMulticast(): bool
	{
		return $this->matchesAny(self::MulticastRanges);
	}


	/**
	 * Tests IANA-reserved ranges not covered by other predicates: documentation prefixes
	 * (192.0.2.0/24, 2001:db8::/32), CGNAT (100.64.0.0/10), benchmarking, future-use,
	 * unspecified (0.0.0.0/8, ::/128) and similar.
	 */
	public function isReserved(): bool
	{
		return $this->matchesAny(self::ReservedRanges);
	}


	public function __toString(): string
	{
		return $this->address;
	}


	/**
	 * @param  string[]  $ranges
	 */
	private function matchesAny(array $ranges): bool
	{
		foreach ($ranges as $cidr) {
			if ($this->isInRange($cidr)) {
				return true;
			}
		}
		return false;
	}
}
