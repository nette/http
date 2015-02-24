<?php

/**
 * This file is part of the Nette Framework (http://nette.org)
 * Copyright (c) 2004 David Grudl (http://davidgrudl.com)
 */

namespace Nette\Bridges\HttpDI;

use Nette;


/**
 * Session extension for Nette DI.
 *
 * @author     David Grudl
 */
class SessionExtension extends Nette\DI\CompilerExtension
{
	public $defaults = array(
		'debugger' => FALSE,
		'autoStart' => 'smart', // true|false|smart
		'expiration' => NULL,
	);

	/** @var bool */
	private $debugMode;


	public function __construct($debugMode = FALSE)
	{
		$this->debugMode = $debugMode;
	}


	public function loadConfiguration()
	{
		$container = $this->getContainerBuilder();
		$config = $this->getConfig() + $this->defaults;
		$this->setConfig($config);

		$session = $container->addDefinition($this->prefix('session'))
			->setClass('Nette\Http\Session');

		if ($config['expiration']) {
			$session->addSetup('setExpiration', array($config['expiration']));
		}

		if ($this->debugMode && $config['debugger']) {
			$session->addSetup('@Tracy\Bar::addPanel', array(
				new Nette\DI\Statement('Nette\Bridges\HttpTracy\SessionPanel')
			));
		}

		unset($config['expiration'], $config['autoStart'], $config['debugger']);
		if (!empty($config)) {
			$session->addSetup('setOptions', array($config));
		}

		if ($this->name === 'session') {
			$container->addAlias('session', $this->prefix('session'));
		}
	}


	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		if (PHP_SAPI === 'cli') {
			return;
		}

		$initialize = $class->getMethod('initialize');
		$config = $this->getConfig();
		$name = $this->prefix('session');

		if ($config['autoStart'] === 'smart') {
			$initialize->addBody('$this->getService(?)->exists() && $this->getService(?)->start();', array($name, $name));

		} elseif ($config['autoStart']) {
			$initialize->addBody('$this->getService(?)->start();', array($name));
		}
	}

}
