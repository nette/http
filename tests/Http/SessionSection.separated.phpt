<?php

/**
 * Test: Nette\Http\SessionSection separated space.
 */

declare(strict_types=1);

use Nette\Http\Session;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$session = new Session(new Nette\Http\Request(new Nette\Http\UrlScript), new Nette\Http\Response);

$section1 = $session->getSection('namespace1');
$section1b = $session->getSection('namespace1');
$section2 = $session->getSection('namespace2');
$section2b = $session->getSection('namespace2');
$section3 = $session->getSection('default');
$section3b = $session->getSection('default');
$section1->set('a', 'apple');
$section2->set('a', 'pear');
$section3->set('a', 'orange');
Assert::same('apple', $section1->get('a'));
Assert::same('apple', $section1b->get('a'));
Assert::same('pear', $section2->get('a'));
Assert::same('pear', $section2b->get('a'));
Assert::same('orange', $section3->get('a'));
Assert::same('orange', $section3b->get('a'));
