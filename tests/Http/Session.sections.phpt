<?php

/**
 * Test: Nette\Http\Session sections.
 */

declare(strict_types=1);

use Nette\Http\Session;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


ob_start();

$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);

Assert::false($session->hasSection('trees')); // hasSection() should have returned false for a section with no keys set

$section = $session->getSection('trees');
Assert::type(Nette\Http\SessionSection::class, $section);
Assert::false($session->hasSection('trees')); // hasSection() should have returned false for a section with no keys set

$section->set('hello', 'world');
Assert::true($session->hasSection('trees')); // hasSection() should have returned true for a section with keys set

$section = $session->getSection('default');
Assert::same(['trees'], $session->getSectionNames());

$section->set('hello', 'world');
Assert::same(['trees', 'default'], $session->getSectionNames());
