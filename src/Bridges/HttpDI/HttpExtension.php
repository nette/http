<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Bridges\HttpDI;

use Nette;
use Nette\PhpGenerator\Helpers;


/**
 * HTTP extension for Nette DI.
 */
class HttpExtension extends Nette\DI\CompilerExtension
{
	public $defaults = [
		'proxy' => [],
		'headers' => [
			'X-Powered-By' => 'Nette Framework 3',
			'Content-Type' => 'text/html; charset=utf-8',
		],
		'frames' => 'SAMEORIGIN', // X-Frame-Options
		'csp' => [], // Content-Security-Policy
		'cspReportOnly' => [], // Content-Security-Policy-Report-Only
		'featurePolicy' => [], // Feature-Policy
		'cookieSecure' => 'auto', // true|false|auto  Whether the cookie is available only through HTTPS
		'sameSiteProtection' => true, // activates Request::isSameSite() protection
	];

	/** @var bool */
	private $cliMode;


	public function __construct(bool $cliMode = false)
	{
		$this->cliMode = $cliMode;
	}


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$builder->addDefinition($this->prefix('requestFactory'))
			->setFactory(Nette\Http\RequestFactory::class)
			->addSetup('setProxy', [$config['proxy']]);

		$builder->addDefinition($this->prefix('request'))
			->setFactory('@Nette\Http\RequestFactory::createHttpRequest')
			->setType(Nette\Http\IRequest::class)
			->setExported();

		$response = $builder->addDefinition($this->prefix('response'))
			->setFactory(Nette\Http\Response::class)
			->setType(Nette\Http\IResponse::class)
			->setExported();

		if (isset($config['cookieSecure'])) {
			$value = $config['cookieSecure'] === 'auto'
				? $builder::literal('$this->getService(?)->isSecured()', [$this->prefix('request')])
				: (bool) $config['cookieSecure'];
			$response->addSetup('$cookieSecure', [$value]);
		}

		if ($this->name === 'http') {
			$builder->addAlias('nette.httpRequestFactory', $this->prefix('requestFactory'));
			$builder->addAlias('httpRequest', $this->prefix('request'));
			$builder->addAlias('httpResponse', $this->prefix('response'));

			$builder->addDefinition($this->prefix('oldRequest'))
				->setFactory($this->prefix('@request'))
				->setType(Nette\Http\Request::class)
				->addSetup('::trigger_error', ['Service Nette\Http\Request should be autowired via interface Nette\Http\IRequest.', E_USER_DEPRECATED])
				->setAutowired(Nette\Http\Request::class);

			$builder->addDefinition($this->prefix('oldResponse'))
				->setFactory($this->prefix('@response'))
				->setType(Nette\Http\Response::class)
				->addSetup('::trigger_error', ['Service Nette\Http\Response should be autowired via interface Nette\Http\IResponse.', E_USER_DEPRECATED])
				->setAutowired(Nette\Http\Response::class);
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

		if (isset($config['frames']) && $config['frames'] !== true && !isset($headers['X-Frame-Options'])) {
			$frames = $config['frames'];
			if ($frames === false) {
				$frames = 'DENY';
			} elseif (preg_match('#^https?:#', $frames)) {
				$frames = "ALLOW-FROM $frames";
			}
			$headers['X-Frame-Options'] = $frames;
		}

		$code = [];
		foreach (['csp', 'cspReportOnly'] as $key) {
			if (empty($config[$key])) {
				continue;
			}
			$value = self::buildPolicy($config[$key]);
			if (strpos($value, "'nonce'")) {
				$code[0] = '$cspNonce = base64_encode(random_bytes(16));';
				$value = Nette\DI\ContainerBuilder::literal(
					'str_replace(?, ? . $cspNonce, ?)',
					["'nonce", "'nonce-", $value]
				);
			}
			$headers['Content-Security-Policy' . ($key === 'csp' ? '' : '-Report-Only')] = $value;
		}

		if (!empty($config['featurePolicy'])) {
			$headers['Feature-Policy'] = self::buildPolicy($config['featurePolicy']);
		}

		$code[] = Helpers::formatArgs('$response = $this->getService(?);', [$this->prefix('response')]);
		foreach ($headers as $key => $value) {
			if ($value != null) { // intentionally ==
				$code[] = Helpers::formatArgs('$response->setHeader(?, ?);', [$key, $value]);
			}
		}

		if (!empty($config['sameSiteProtection'])) {
			$code[] = Helpers::formatArgs('$response->setCookie(...?);', [['nette-samesite', '1', 0, '/', null, null, true, 'Strict']]);
		}

		$initialize->addBody("(function () {\n\t" . implode("\n\t", $code) . "\n})();");
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
				$value .= !isset($nonQuoted[$type]) && preg_match('#^[a-z-]+\z#', $item) ? " '$item'" : " $item";
			}
			$value .= '; ';
		}
		return $value;
	}
}
