<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Http;

use function array_column, array_merge, dns_get_record, in_array, parse_url, str_ends_with, str_starts_with, strlen, strtolower, substr;
use const DNS_A, DNS_AAAA;


/**
 * Validates URLs against a configurable policy: scheme, port, host allow/blocklist,
 * userinfo, and resolved IP ranges. Used to guard server-side URL fetches against
 * SSRF — set the policy to match your threat model.
 */
final class UrlValidator
{
	/** Implicit ports for known schemes (used when URL has no explicit port). */
	private const SchemePorts = ['http' => 80, 'https' => 443];


	/**
	 * Host patterns: exact ('example.com') or wildcard subdomain ('*.example.com'
	 * matches any depth but NOT apex; for apex list both forms). Multicast addresses
	 * are always rejected — never opt-in.
	 *
	 * @param  string[]       $schemes         allowed schemes; empty array rejects all
	 * @param  int[]|null     $ports           allowed ports, null = any; implicit port from scheme (https→443, http→80) is honored
	 * @param  string[]|null  $hostAllowlist   if set, host must match one pattern; null = no allowlist; [] = reject all
	 * @param  string[]|null  $hostBlocklist   if set, host must not match any pattern
	 */
	public function __construct(
		private array $schemes = ['https'],
		private ?array $ports = [443],
		private bool $allowPrivateIps = false,
		private bool $allowLoopback = false,
		private bool $allowLinkLocal = false,
		private bool $allowReserved = false,
		private bool $allowUserinfo = false,
		private ?array $hostAllowlist = null,
		private ?array $hostBlocklist = null,
	) {
	}


	/**
	 * Returns true if URL passes the entire policy: parseable, allowed scheme/port,
	 * userinfo policy, host allow/blocklist, and (for hostname hosts) every DNS-resolved
	 * A/AAAA address passes the IP-range policy. DNS lookup is skipped when host is an
	 * IP literal. Null URL returns false.
	 */
	public function allows(string|UrlImmutable|null $url): bool
	{
		return $this->getResolvedIPs($url) !== [];
	}


	/**
	 * Same as allows() without DNS resolution and IP-range checks. Useful as a
	 * fast pre-filter, or when DNS validation is delegated to the fetch layer
	 * (e.g. CURLOPT_RESOLVE).
	 */
	public function allowsWithoutDns(string|UrlImmutable|null $url): bool
	{
		return $this->parseAndValidate($url) !== null;
	}


	/**
	 * Returns DNS-resolved IPs that passed the full policy, or [] on any failure.
	 * Pass returned IPs to your HTTP client's connection pinning (CURLOPT_RESOLVE)
	 * to bind the actual fetch to validated addresses, defeating DNS rebinding race.
	 *
	 * @return string[]  A records first, then AAAA, in dotted-quad / RFC 5952 form
	 */
	public function getResolvedIPs(string|UrlImmutable|null $url): array
	{
		$host = $this->parseAndValidate($url);
		if ($host === null) {
			return [];
		}

		// IP literal: validate directly, no DNS
		if (IPAddress::isValid($host)) {
			return $this->isIPAllowed(new IPAddress($host)) ? [$host] : [];
		}

		// Hostname: resolve and validate every A/AAAA record
		$ips = self::resolveHost($host);
		if (!$ips) {
			return [];
		}
		foreach ($ips as $ip) {
			$addr = IPAddress::tryFrom($ip);
			if ($addr === null || !$this->isIPAllowed($addr)) {
				return [];
			}
		}
		return $ips;
	}


	/**
	 * Returns the host (with brackets stripped from IPv6 literals) on policy pass,
	 * null otherwise. Performs no DNS resolution.
	 */
	private function parseAndValidate(string|UrlImmutable|null $url): ?string
	{
		if ($url === null) {
			return null;
		}

		$parts = parse_url((string) $url);
		if ($parts === false || empty($parts['host']) || empty($parts['scheme'])) {
			return null;
		}
		$scheme = strtolower($parts['scheme']);
		$host = $parts['host'];

		// Strip brackets from IPv6 host literal (parse_url leaves them in)
		if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
			$host = substr($host, 1, -1);
		}
		if ($host === '') {
			return null;
		}

		if (!$this->allowUserinfo && (isset($parts['user']) || isset($parts['pass']))) {
			return null;
		}

		if (!in_array($scheme, $this->schemes, true)) {
			return null;
		}

		if ($this->ports !== null) {
			$effectivePort = $parts['port'] ?? self::SchemePorts[$scheme] ?? null;
			if ($effectivePort === null || !in_array($effectivePort, $this->ports, true)) {
				return null;
			}
		}

		if ($this->hostAllowlist !== null && !self::matchesAnyPattern($host, $this->hostAllowlist)) {
			return null;
		}
		if ($this->hostBlocklist !== null && self::matchesAnyPattern($host, $this->hostBlocklist)) {
			return null;
		}

		return $host;
	}


	private function isIPAllowed(IPAddress $ip): bool
	{
		if ($ip->isMulticast()) {
			return false;
		}
		if ($ip->isLoopback()) {
			return $this->allowLoopback;
		}
		if ($ip->isLinkLocal()) {
			return $this->allowLinkLocal;
		}
		if ($ip->isPrivate()) {
			return $this->allowPrivateIps;
		}
		if ($ip->isReserved()) {
			return $this->allowReserved;
		}
		return true;
	}


	/**
	 * @param  string[]  $patterns
	 */
	private static function matchesAnyPattern(string $host, array $patterns): bool
	{
		$host = strtolower($host);
		foreach ($patterns as $pattern) {
			$pattern = strtolower($pattern);
			if (str_starts_with($pattern, '*.')) {
				$suffix = substr($pattern, 1); // '.example.com'
				if (strlen($host) > strlen($suffix) && str_ends_with($host, $suffix)) {
					return true;
				}
			} elseif ($host === $pattern) {
				return true;
			}
		}
		return false;
	}


	/**
	 * @return string[]
	 */
	private static function resolveHost(string $host): array
	{
		$a = @dns_get_record($host, DNS_A) ?: [];
		$aaaa = @dns_get_record($host, DNS_AAAA) ?: [];
		return array_merge(
			array_column($a, 'ip'),
			array_column($aaaa, 'ipv6'),
		);
	}
}
