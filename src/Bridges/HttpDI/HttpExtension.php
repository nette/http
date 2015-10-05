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
	public $defaults = array(
		'proxy' => array(),
		'headers' => array(
			'X-Powered-By' => 'Nette Framework',
			'Content-Type' => 'text/html; charset=utf-8',
		),
		'frames' => 'SAMEORIGIN', // X-Frame-Options
	);


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->validateConfig($this->defaults);

		$container->addDefinition($this->prefix('requestFactory'))
			->setClass('Nette\Http\RequestFactory')
			->addSetup('setProxy', array($config['proxy']));

		$container->addDefinition($this->prefix('request'))
			->setClass('Nette\Http\Request')
			->setFactory('@Nette\Http\RequestFactory::createHttpRequest');

		$container->addDefinition($this->prefix('response'))
			->setClass('Nette\Http\Response');

		$container->addDefinition($this->prefix('context'))
			->setClass('Nette\Http\Context');

		if ($this->name === 'http') {
			$container->addAlias('nette.httpRequestFactory', $this->prefix('requestFactory'));
			$container->addAlias('nette.httpContext', $this->prefix('context'));
			$container->addAlias('httpRequest', $this->prefix('request'));
			$container->addAlias('httpResponse', $this->prefix('response'));
		}
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		if (PHP_SAPI === 'cli') {
			return;
		}

		$initialize = $class->getMethod('initialize');
		$config = $this->getConfig();

		if (isset($config['frames']) && $config['frames'] !== TRUE) {
			$frames = $config['frames'];
			if ($frames === FALSE) {
				$frames = 'DENY';
			} elseif (preg_match('#^https?:#', $frames)) {
				$frames = "ALLOW-FROM $frames";
			}
			$initialize->addBody('header(?);', array("X-Frame-Options: $frames"));
		}

		foreach ($config['headers'] as $key => $value) {
			if ($value != NULL) { // intentionally ==
				$initialize->addBody('header(?);', array("$key: $value"));
			}
		}
	}

}
