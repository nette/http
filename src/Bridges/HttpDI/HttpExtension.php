<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\HttpDI;

use Nette;
use Nette\Schema\Expect;


/**
 * HTTP extension for Nette DI.
 */
class HttpExtension extends Nette\DI\CompilerExtension
{
	/** @var bool */
	private $cliMode;


	public function __construct(bool $cliMode = false)
	{
		$this->cliMode = $cliMode;
	}


	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Expect::structure([
			'proxy' => Expect::anyOf(Expect::arrayOf('string'), Expect::string()->castTo('array'))->default([])->dynamic(),
			'headers' => Expect::arrayOf('scalar|null')->default([
				'X-Powered-By' => 'Nette Framework 3',
				'Content-Type' => 'text/html; charset=utf-8',
			]),
			'frames' => Expect::anyOf(Expect::string(), Expect::bool(), null)->default('SAMEORIGIN'), // X-Frame-Options
			'csp' => Expect::arrayOf('array|scalar|null'), // Content-Security-Policy
			'cspReportOnly' => Expect::arrayOf('array|scalar|null'), // Content-Security-Policy-Report-Only
			'featurePolicy' => Expect::arrayOf('array|scalar|null'), // Feature-Policy
			'cookieSecure' => Expect::anyOf(null, true, false, 'auto'), // true|false|auto  Whether the cookie is available only through HTTPS
		]);
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->config;

		$builder->addDefinition($this->prefix('requestFactory'))
			->setFactory(Nette\Http\RequestFactory::class)
			->addSetup('setProxy', [$config->proxy]);

		$builder->addDefinition($this->prefix('request'))
			->setFactory('@Nette\Http\RequestFactory::fromGlobals');

		$response = $builder->addDefinition($this->prefix('response'))
			->setFactory(Nette\Http\Response::class);

		if ($config->cookieSecure !== null) {
			$value = $config->cookieSecure === 'auto'
				? $builder::literal('$this->getService(?)->isSecured()', [$this->prefix('request')])
				: $config->cookieSecure;
			$response->addSetup('$cookieSecure', [$value]);
		}

		if ($this->name === 'http') {
			$builder->addAlias('nette.httpRequestFactory', $this->prefix('requestFactory'));
			$builder->addAlias('httpRequest', $this->prefix('request'));
			$builder->addAlias('httpResponse', $this->prefix('response'));
		}

		if (!$this->cliMode) {
			$this->sendHeaders();
		}
	}


	private function sendHeaders()
	{
		$config = $this->config;
		$headers = array_map('strval', $config->headers);

		if (isset($config->frames) && $config->frames !== true && !isset($headers['X-Frame-Options'])) {
			$frames = $config->frames;
			if ($frames === false) {
				$frames = 'DENY';
			} elseif (preg_match('#^https?:#', $frames)) {
				$frames = "ALLOW-FROM $frames";
			}
			$headers['X-Frame-Options'] = $frames;
		}

		foreach (['csp', 'cspReportOnly'] as $key) {
			if (empty($config->$key)) {
				continue;
			}
			$value = self::buildPolicy($config->$key);
			if (strpos($value, "'nonce'")) {
				$this->initialization->addBody('$cspNonce = base64_encode(random_bytes(16));');
				$value = Nette\DI\ContainerBuilder::literal(
					'str_replace(?, ? . $cspNonce, ?)',
					["'nonce", "'nonce-", $value]
				);
			}
			$headers['Content-Security-Policy' . ($key === 'csp' ? '' : '-Report-Only')] = $value;
		}

		if (!empty($config->featurePolicy)) {
			$headers['Feature-Policy'] = self::buildPolicy($config->featurePolicy);
		}

		$this->initialization->addBody('$response = $this->getService(?);', [$this->prefix('response')]);
		foreach ($headers as $key => $value) {
			if ($value !== '') {
				$this->initialization->addBody('$response->setHeader(?, ?);', [$key, $value]);
			}
		}

		$this->initialization->addBody('$response->setCookie(...?);', [['nette-samesite', '1', 0, '/', null, null, true, 'Strict']]);
	}


	private static function buildPolicy(array $config): string
	{
		static $nonQuoted = ['require-sri-for' => 1, 'sandbox' => 1];
		$value = '';
		foreach ($config as $type => $policy) {
			if ($policy === false) {
				continue;
			}
			$policy = $policy === true ? [] : (array) $policy;
			$value .= $type;
			foreach ($policy as $item) {
				if (is_array($item)) {
					$item = key($item) . ':';
				}
				$value .= !isset($nonQuoted[$type]) && preg_match('#^[a-z-]+$#D', $item) ? " '$item'" : " $item";
			}
			$value .= '; ';
		}
		return $value;
	}
}
