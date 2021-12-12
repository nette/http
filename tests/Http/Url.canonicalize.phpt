<?php

/**
 * Test: Nette\Http\Url canonicalize.
 */

declare(strict_types=1);

use Nette\Http\Url;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


$url = new Url('http://hostname/path?arg=value&arg2=v%20a%26l%3Du%2Be');
$url->canonicalize();
Assert::same('http://hostname/path?arg=value&arg2=v%20a%26l%3Du%2Be', (string) $url);


$url = new Url('http://username%3A:password%3A@hostN%61me:60/p%61th%2f%25()?arg=value&arg2=v%20a%26l%3Du%2Be#%61nchor');
$url->canonicalize();
Assert::same('http://username%3A:password%3A@hostname:60/path%2F%25()?arg=value&arg2=v%20a%26l%3Du%2Be#anchor', (string) $url);


$url = new Url('http://host/%1f%20 %21!%22"%23%24$%25%26&%27\'%28(%29)%2a*%2b+%2c,%2d-%2e.%2f/%300%311%322%333%344%355%366%377%388%399%3a:%3b;%3c<%3d=%3e>%3f%40@'
	. '%41A%42B%43C%44D%45E%46F%47G%48H%49I%4aJ%4bK%4cL%4dM%4eN%4fO%50P%51Q%52R%53S%54T%55U%56V%57W%58X%59Y%5aZ%5b[%5c\%5d]%5e^%5f_%60`'
	. '%61a%62b%63c%64d%65e%66f%67g%68h%69i%6aj%6bk%6cl%6dm%6en%6fo%70p%71q%72r%73s%74t%75u%76v%77w%78x%79y%7az%7b{%7c|%7d}%7e~%7fá');
$url->canonicalize();
Assert::same('http://host/%1F%20%20!!%22%22%23$$%25&&\'\'(())**++,,--..%2F/00112233445566778899::;;%3C%3C==%3E%3E%3F@@'
	. 'AABBCCDDEEFFGGHHIIJJKKLLMMNNOOPPQQRRSSTTUUVVWWXXYYZZ%5B%5B%5C%5C%5D%5D%5E%5E__%60%60'
	. 'aabbccddeeffgghhiijjkkllmmnnooppqqrrssttuuvvwwxxyyzz%7B%7B%7C%7C%7D%7D~~%7F%C3%A1', (string) $url);


$url = new Url('https://xn--tst-qla.de/');
$url->canonicalize();
Assert::same('https://täst.de/', (string) $url);
