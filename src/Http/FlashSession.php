<?php

namespace Nette\Http;

use Nette;


class FlashSession extends Nette\Object
{

	const DEFAULT_LIFETIME = '10 seconds';

	/** @var string */
	private $id;

	/** @var Session */
	private $session;


	/**
	 * @param string
	 */
	public function __construct($id, Session $session)
	{
		$this->id = $id;
		$this->session = $session;
	}


	/**
	 * @param  string
	 * @return SessionSection
	 */
	public function getSection($namespace)
	{
		return $this->session->getSection($namespace . '/' . $this->id)
			->setExpiration(self::DEFAULT_LIFETIME);
	}


	/**
	 * @return bool
	 */
	public function exists($namespace)
	{
		return $this->session->hasSection($namespace . '/' . $this->id);
	}


	/** @return string */
	public static function generateID()
	{
		return Nette\Utils\Random::generate(4);
	}

}
