<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

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
			'X-Powered-By' => 'Nette Framework 3',
			'Content-Type' => 'text/html; charset=utf-8',
		],
		'frames' => 'SAMEORIGIN', // X-Frame-Options
		'csp' => [], // Content-Security-Policy
		'csp-report' => [], // Content-Security-Policy-Report-Only
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
			->setClass(Nette\Http\IRequest::class);

		$builder->addDefinition($this->prefix('response'))
			->setFactory(Nette\Http\Response::class)
			->setClass(Nette\Http\IResponse::class);

		if ($this->name === 'http') {
			$builder->addAlias('nette.httpRequestFactory', $this->prefix('requestFactory'));
			$builder->addAlias('httpRequest', $this->prefix('request'));
			$builder->addAlias('httpResponse', $this->prefix('response'));

			$builder->addDefinition($this->prefix('oldRequest'))
				->setFactory($this->prefix('@request'))
				->setClass(Nette\Http\Request::class)
				->addSetup('::trigger_error', ['Service Nette\Http\Request should be autowired via interface Nette\Http\IRequest.', E_USER_DEPRECATED])
				->setAutowired(Nette\Http\Request::class);

			$builder->addDefinition($this->prefix('oldResponse'))
				->setFactory($this->prefix('@response'))
				->setClass(Nette\Http\Response::class)
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

		foreach (['csp', 'csp-report'] as $key) {
			if (empty($config[$key])) {
				continue;
			}
			$value = '';
			foreach ($config[$key] as $type => $policy) {
				$value .= $type;
				foreach ((array) $policy as $item) {
					$value .= preg_match('#^[a-z-]+\z#', $item) ? " '$item'" : " $item";
				}
				$value .= '; ';
			}
			if (strpos($value, "'nonce'")) {
				$value = Nette\DI\ContainerBuilder::literal(
					'str_replace(?, ? . ($cspNonce = $cspNonce \?\? base64_encode(random_bytes(16))), ?)',
					["'nonce", "'nonce-", $value]
				);
			}
			$headers['Content-Security-Policy' . ($key === 'csp' ? '' : '-Report-Only')] = $value;
		}

		foreach ($headers as $key => $value) {
			if ($value != null) { // intentionally ==
				$initialize->addBody('$this->getService(?)->setHeader(?, ?);', [$this->prefix('response'), $key, $value]);
			}
		}
	}
}
