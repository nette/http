<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\Bridges\HttpDI;

use Nette;


/**
 * HTTP extension for Nette DI.
 */
class HttpExtension extends Nette\DI\CompilerExtension
{
	public $defaults = [
		'proxy' => [],
		'headers' => [
			'X-Powered-By' => 'Nette Framework',
			'Content-Type' => 'text/html; charset=utf-8',
		],
		'frames' => 'SAMEORIGIN', // X-Frame-Options
		'csp' => [], // Content-Security-Policy
		'cspReportOnly' => [], // Content-Security-Policy-Report-Only
		'csp-report' => null, // for compatibility
		'featurePolicy' => [], // Feature-Policy
		'cookieSecure' => null, // true|false|auto  Whether the cookie is available only through HTTPS
		'sameSiteProtection' => null, // activates Request::isSameSite() protection
	];

	/** @var bool */
	private $cliMode;


	public function __construct($cliMode = false)
	{
		$this->cliMode = $cliMode;
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$builder->addDefinition($this->prefix('requestFactory'))
			->setClass(Nette\Http\RequestFactory::class)
			->addSetup('setProxy', [$config['proxy']]);

		$builder->addDefinition($this->prefix('request'))
			->setClass(Nette\Http\Request::class)
			->setFactory('@Nette\Http\RequestFactory::createHttpRequest');

		$builder->addDefinition($this->prefix('response'))
			->setClass(Nette\Http\Response::class);

		$builder->addDefinition($this->prefix('context'))
			->setClass(Nette\Http\Context::class)
			->addSetup('::trigger_error', ['Service http.context is deprecated.', E_USER_DEPRECATED]);

		if ($this->name === 'http') {
			$builder->addAlias('nette.httpRequestFactory', $this->prefix('requestFactory'));
			$builder->addAlias('nette.httpContext', $this->prefix('context'));
			$builder->addAlias('httpRequest', $this->prefix('request'));
			$builder->addAlias('httpResponse', $this->prefix('response'));
		}
	}


	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();
		if (isset($this->config['cookieSecure'])) {
			$value = $this->config['cookieSecure'] === 'auto'
				? $builder::literal('$this->getService(?)->isSecured()', [$this->prefix('request')])
				: (bool) $this->config['cookieSecure'];

			$builder->getDefinition($this->prefix('response'))
				->addSetup('$cookieSecure', [$value]);
			$builder->getDefinitionByType(Nette\Http\Session::class)
				->addSetup('setOptions', [['cookie_secure' => $value]]);
		}
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		if ($this->cliMode) {
			return;
		}

		$initialize = $class->getMethod('initialize');
		$config = $this->getConfig();
		$headers = $config['headers'];

		if (isset($config['frames']) && $config['frames'] !== true) {
			$frames = $config['frames'];
			if ($frames === false) {
				$frames = 'DENY';
			} elseif (preg_match('#^https?:#', $frames)) {
				$frames = "ALLOW-FROM $frames";
			}
			$headers['X-Frame-Options'] = $frames;
		}

		if (isset($config['csp-report'])) {
			trigger_error('Rename csp-repost to cspReportOnly in config.', E_USER_DEPRECATED);
			$config['cspReportOnly'] = $config['csp-report'];
		}

		foreach (['csp', 'cspReportOnly'] as $key) {
			if (empty($config[$key])) {
				continue;
			}
			$value = self::buildPolicy($config[$key]);
			if (strpos($value, "'nonce'")) {
				$value = Nette\DI\ContainerBuilder::literal(
					'str_replace(?, ? . (isset($cspNonce) \? $cspNonce : $cspNonce = base64_encode(Nette\Utils\Random::generate(16, "\x00-\xFF"))), ?)',
					["'nonce", "'nonce-", $value]
				);
			}
			$headers['Content-Security-Policy' . ($key === 'csp' ? '' : '-Report-Only')] = $value;
		}

		if (!empty($config['featurePolicy'])) {
			$headers['Feature-Policy'] = self::buildPolicy($config['featurePolicy']);
		}

		foreach ($headers as $key => $value) {
			if ($value != null) { // intentionally ==
				$initialize->addBody('$this->getService(?)->setHeader(?, ?);', [$this->prefix('response'), $key, $value]);
			}
		}

		if (!empty($config['sameSiteProtection'])) {
			$initialize->addBody('$this->getService(?)->setCookie(...?);', [$this->prefix('response'), ['nette-samesite', '1', 0, '/', null, null, true, 'Strict']]);
		}
	}


	private static function buildPolicy(array $config)
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
				$value .= !isset($nonQuoted[$type]) && preg_match('#^[a-z-]+\z#', $item) ? " '$item'" : " $item";
			}
			$value .= '; ';
		}
		return $value;
	}
}
