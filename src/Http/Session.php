<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;

use Nette;


/**
 * Provides access to session sections as well as session settings and management methods.
 */
class Session
{
	use Nette\SmartObject;

	/** Default file lifetime */
	private const DefaultFileLifetime = 3 * Nette\Utils\DateTime::HOUR;

	private const SecurityOptions = [
		'referer_check' => '',    // must be disabled because PHP implementation is invalid
		'use_cookies' => 1,       // must be enabled to prevent Session Hijacking and Fixation
		'use_only_cookies' => 1,  // must be enabled to prevent Session Fixation
		'use_trans_sid' => 0,     // must be disabled to prevent Session Hijacking and Fixation
		'use_strict_mode' => 1,   // must be enabled to prevent Session Fixation
		'cookie_httponly' => true, // must be enabled to prevent Session Hijacking
	];

	/** @var array<callable(self): void>  Occurs when the session is started */
	public $onStart = [];

	/** @var array<callable(self): void>  Occurs before the session is written to disk */
	public $onBeforeWrite = [];

	/** @var bool  has been session ID regenerated? */
	private $regenerated = false;

	/** @var bool  has been session started by Nette? */
	private $started = false;

	/** @var array default configuration */
	private $options = [
		'cookie_samesite' => IResponse::SameSiteLax,
		'cookie_lifetime' => 0,   // for a maximum of 3 hours or until the browser is closed
		'gc_maxlifetime' => self::DefaultFileLifetime, // 3 hours
	];

	/** @var IRequest */
	private $request;

	/** @var IResponse */
	private $response;

	/** @var \SessionHandlerInterface */
	private $handler;

	/** @var bool */
	private $readAndClose = false;

	/** @var bool */
	private $fileExists = true;

	/** @var bool */
	private $autoStart = true;


	public function __construct(IRequest $request, IResponse $response)
	{
		$this->request = $request;
		$this->response = $response;
		$this->options['cookie_path'] = &$this->response->cookiePath;
		$this->options['cookie_domain'] = &$this->response->cookieDomain;
		$this->options['cookie_secure'] = &$this->response->cookieSecure;
	}


	/**
	 * Starts and initializes session data.
	 * @throws Nette\InvalidStateException
	 */
	public function start(): void
	{
		$this->doStart();
	}


	private function doStart($mustExists = false): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) { // adapt an existing session
			if (!$this->started) {
				$this->configure(self::SecurityOptions);
				$this->initialize();
			}

			return;
		}

		$this->configure(self::SecurityOptions + $this->options);

		if (!session_id()) { // session is started for first time
			$id = $this->request->getCookie(session_name());
			$id = is_string($id) && preg_match('#^[0-9a-zA-Z,-]{22,256}$#Di', $id)
				? $id
				: session_create_id();
			session_id($id); // causes resend of a cookie to make sure it has the right parameters
		}

		try {
			// session_start returns false on failure only sometimes (even in PHP >= 7.1)
			Nette\Utils\Callback::invokeSafe(
				'session_start',
				[['read_and_close' => $this->readAndClose]],
				function (string $message) use (&$e): void {
					$e = new Nette\InvalidStateException($message);
				}
			);
		} catch (\Throwable $e) {
		}

		if ($e) {
			@session_write_close(); // this is needed
			throw $e;
		}

		if ($mustExists && $this->request->getCookie(session_name()) !== session_id()) {
			// PHP regenerated the ID which means that the session did not exist and cookie was invalid
			$this->destroy();
			return;
		}

		$this->initialize();
		Nette\Utils\Arrays::invoke($this->onStart, $this);
	}


	/** @internal */
	public function autoStart(bool $forWrite): void
	{
		if ($this->started || (!$forWrite && !$this->exists())) {
			return;

		} elseif (!$this->autoStart) {
			trigger_error('Cannot auto-start session because autostarting is disabled', E_USER_WARNING);
			return;
		}

		$this->doStart(!$forWrite);
	}


	private function initialize(): void
	{
		$this->started = true;
		$this->fileExists = true;

		/* structure:
			__NF: Data, Meta, Time
				DATA: section->variable = data
				META: section->variable = Timestamp
		*/
		$nf = &$_SESSION['__NF'];

		if (!is_array($nf)) {
			$nf = [];
		}

		// regenerate empty session
		if (empty($nf['Time']) && !$this->readAndClose) {
			$nf['Time'] = time();
			if ($this->request->getCookie(session_name()) === session_id()) {
				// ensures that the session was created with use_strict_mode (ie by Nette)
				$this->regenerateId();
			}
		}

		// expire section variables
		$now = time();
		foreach ($nf['META'] ?? [] as $section => $metadata) {
			foreach ($metadata ?? [] as $variable => $value) {
				if (!empty($value['T']) && $now > $value['T']) {
					if ($variable === '') { // expire whole section
						unset($nf['META'][$section], $nf['DATA'][$section]);
						continue 2;
					}

					unset($nf['META'][$section][$variable], $nf['DATA'][$section][$variable]);
				}
			}
		}
	}


	public function __destruct()
	{
		$this->clean();
	}


	/**
	 * Has been session started?
	 */
	public function isStarted(): bool
	{
		return $this->started && session_status() === PHP_SESSION_ACTIVE;
	}


	/**
	 * Ends the current session and store session data.
	 */
	public function close(): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			$this->clean();
			session_write_close();
			$this->started = false;
		}
	}


	/**
	 * Destroys all data registered to a session.
	 */
	public function destroy(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			throw new Nette\InvalidStateException('Session is not started.');
		}

		session_destroy();
		$_SESSION = null;
		$this->started = false;
		$this->fileExists = false;
		if (!$this->response->isSent()) {
			$params = session_get_cookie_params();
			$this->response->deleteCookie(session_name(), $params['path'], $params['domain'], $params['secure']);
		}
	}


	/**
	 * Does session exist for the current request?
	 */
	public function exists(): bool
	{
		return session_status() === PHP_SESSION_ACTIVE
			|| ($this->fileExists && $this->request->getCookie($this->getName()));
	}


	/**
	 * Regenerates the session ID.
	 * @throws Nette\InvalidStateException
	 */
	public function regenerateId(): void
	{
		if ($this->regenerated) {
			return;
		}

		if (session_status() === PHP_SESSION_ACTIVE) {
			if (headers_sent($file, $line)) {
				throw new Nette\InvalidStateException('Cannot regenerate session ID after HTTP headers have been sent' . ($file ? " (output started at $file:$line)." : '.'));
			}

			session_regenerate_id(true);
		} else {
			session_id(session_create_id());
		}

		$this->regenerated = true;
	}


	/**
	 * Returns the current session ID. Don't make dependencies, can be changed for each request.
	 */
	public function getId(): string
	{
		return session_id();
	}


	/**
	 * Sets the session name to a specified one.
	 * @return static
	 */
	public function setName(string $name)
	{
		if (!preg_match('#[^0-9.][^.]*$#DA', $name)) {
			throw new Nette\InvalidArgumentException('Session name cannot contain dot.');
		}

		session_name($name);
		return $this->setOptions([
			'name' => $name,
		]);
	}


	/**
	 * Gets the session name.
	 */
	public function getName(): string
	{
		return $this->options['name'] ?? session_name();
	}


	/********************* sections management ****************d*g**/


	/**
	 * Returns specified session section.
	 * @throws Nette\InvalidArgumentException
	 */
	public function getSection(string $section, string $class = SessionSection::class): SessionSection
	{
		return new $class($this, $section);
	}


	/**
	 * Checks if a session section exist and is not empty.
	 */
	public function hasSection(string $section): bool
	{
		if ($this->exists() && !$this->started) {
			$this->autoStart(false);
		}

		return !empty($_SESSION['__NF']['DATA'][$section]);
	}


	/** @deprecated */
	public function getIterator(): \Iterator
	{
		trigger_error(__METHOD__ . '() is deprecated', E_USER_DEPRECATED);
		if ($this->exists() && !$this->started) {
			$this->autoStart(false);
		}

		return new \ArrayIterator(array_keys($_SESSION['__NF']['DATA'] ?? []));
	}


	/**
	 * Cleans and minimizes meta structures.
	 */
	private function clean(): void
	{
		if (!$this->isStarted()) {
			return;
		}

		Nette\Utils\Arrays::invoke($this->onBeforeWrite, $this);

		$nf = &$_SESSION['__NF'];
		foreach ($nf['META'] ?? [] as $name => $foo) {
			if (empty($nf['META'][$name])) {
				unset($nf['META'][$name]);
			}
		}
	}


	/********************* configuration ****************d*g**/


	/**
	 * Sets session options.
	 * @return static
	 * @throws Nette\NotSupportedException
	 * @throws Nette\InvalidStateException
	 */
	public function setOptions(array $options)
	{
		$normalized = [];
		$allowed = ini_get_all('session', false) + ['session.read_and_close' => 1, 'session.cookie_samesite' => 1]; // for PHP < 7.3

		foreach ($options as $key => $value) {
			if (!strncmp($key, 'session.', 8)) { // back compatibility
				$key = substr($key, 8);
			}

			$normKey = strtolower(preg_replace('#(.)(?=[A-Z])#', '$1_', $key)); // camelCase -> snake_case

			if (!isset($allowed["session.$normKey"])) {
				$hint = substr((string) Nette\Utils\Helpers::getSuggestion(array_keys($allowed), "session.$normKey"), 8);
				if ($key !== $normKey) {
					$hint = preg_replace_callback('#_(.)#', function ($m) { return strtoupper($m[1]); }, $hint); // snake_case -> camelCase
				}

				throw new Nette\InvalidStateException("Invalid session configuration option '$key'" . ($hint ? ", did you mean '$hint'?" : '.'));
			}

			$normalized[$normKey] = $value;
		}

		if (array_key_exists('read_and_close', $normalized)) {
			if (session_status() === PHP_SESSION_ACTIVE) {
				throw new Nette\InvalidStateException('Cannot configure "read_and_close" for already started session.');
			}

			$this->readAndClose = (bool) $normalized['read_and_close'];
			unset($normalized['read_and_close']);
		}

		$this->autoStart = $normalized['auto_start'] ?? true;
		unset($normalized['auto_start']);

		if (session_status() === PHP_SESSION_ACTIVE) {
			$this->configure($normalized);
		}

		$this->options = $normalized + $this->options;
		return $this;
	}


	/**
	 * Returns all session options.
	 */
	public function getOptions(): array
	{
		return $this->options;
	}


	/**
	 * Configures session environment.
	 */
	private function configure(array $config): void
	{
		$special = ['cache_expire' => 1, 'cache_limiter' => 1, 'save_path' => 1, 'name' => 1];
		$cookie = $origCookie = session_get_cookie_params();

		foreach ($config as $key => $value) {
			if ($value === null || ini_get("session.$key") == $value) { // intentionally ==
				continue;

			} elseif (strncmp($key, 'cookie_', 7) === 0) {
				$cookie[substr($key, 7)] = $value;

			} else {
				if (session_status() === PHP_SESSION_ACTIVE) {
					throw new Nette\InvalidStateException("Unable to set 'session.$key' to value '$value' when session has been started" . ($this->started ? '.' : ' by session.auto_start or session_start().'));
				}

				if (isset($special[$key])) {
					("session_$key")($value);

				} elseif (function_exists('ini_set')) {
					ini_set("session.$key", (string) $value);

				} else {
					throw new Nette\NotSupportedException("Unable to set 'session.$key' to '$value' because function ini_set() is disabled.");
				}
			}
		}

		if ($cookie !== $origCookie) {
			if (PHP_VERSION_ID >= 70300) {
				@session_set_cookie_params($cookie); // @ may trigger warning when session is active since PHP 7.2
			} else {
				@session_set_cookie_params( // @ may trigger warning when session is active since PHP 7.2
					$cookie['lifetime'],
					$cookie['path'] . (isset($cookie['samesite']) ? '; SameSite=' . $cookie['samesite'] : ''),
					$cookie['domain'],
					$cookie['secure'],
					$cookie['httponly']
				);
			}

			if (session_status() === PHP_SESSION_ACTIVE) {
				$this->sendCookie();
			}
		}

		if ($this->handler) {
			session_set_save_handler($this->handler);
		}
	}


	/**
	 * Sets the amount of time (like '20 minutes') allowed between requests before the session will be terminated,
	 * null means "for a maximum of 3 hours or until the browser is closed".
	 * @return static
	 */
	public function setExpiration(?string $expire)
	{
		if ($expire === null) {
			return $this->setOptions([
				'gc_maxlifetime' => self::DefaultFileLifetime,
				'cookie_lifetime' => 0,
			]);

		} else {
			$expire = Nette\Utils\DateTime::from($expire)->format('U') - time();
			return $this->setOptions([
				'gc_maxlifetime' => $expire,
				'cookie_lifetime' => $expire,
			]);
		}
	}


	/**
	 * Sets the session cookie parameters.
	 * @return static
	 */
	public function setCookieParameters(
		string $path,
		?string $domain = null,
		?bool $secure = null,
		?string $sameSite = null
	) {
		return $this->setOptions([
			'cookie_path' => $path,
			'cookie_domain' => $domain,
			'cookie_secure' => $secure,
			'cookie_samesite' => $sameSite,
		]);
	}


	/** @deprecated */
	public function getCookieParameters(): array
	{
		trigger_error(__METHOD__ . '() is deprecated.', E_USER_DEPRECATED);
		return session_get_cookie_params();
	}


	/**
	 * Sets path of the directory used to save session data.
	 * @return static
	 */
	public function setSavePath(string $path)
	{
		return $this->setOptions([
			'save_path' => $path,
		]);
	}


	/**
	 * Sets user session handler.
	 * @return static
	 */
	public function setHandler(\SessionHandlerInterface $handler)
	{
		if ($this->started) {
			throw new Nette\InvalidStateException('Unable to set handler when session has been started.');
		}

		$this->handler = $handler;
		return $this;
	}


	/**
	 * Sends the session cookies.
	 */
	private function sendCookie(): void
	{
		$cookie = session_get_cookie_params();
		$this->response->setCookie(
			session_name(),
			session_id(),
			$cookie['lifetime'] ? $cookie['lifetime'] + time() : 0,
			$cookie['path'],
			$cookie['domain'],
			$cookie['secure'],
			$cookie['httponly'],
			$cookie['samesite'] ?? null
		);
	}
}
