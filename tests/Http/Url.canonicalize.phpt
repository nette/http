<?php

/**
 * Test: Nette\Http\Url canonicalize.
 */

use Nette\Http\Url,
	Tester\Assert;


require __DIR__ . '/../bootstrap.php';


$url = new Url('http://hostname/path?arg=value&arg2=v%20a%26l%3Du%2Be');
Assert::same( 'arg=value&arg2=v%20a%26l%3Du%2Be',  $url->query );

$url->canonicalize();
Assert::same( 'arg=value&arg2=v a%26l%3Du%2Be',  $url->query );


$dataset = [
	['http://vps.merxes.cz/%61-%01-%22-%23-%25-%3C-%3E-%5B-%5C-%5D-%5E-%60-%7B-%7C-%7D-%62.txt', 'http://vps.merxes.cz/a-%01-%22-%23-%25-%3C-%3E-%5B-%5C-%5D-%5E-%60-%7B-%7C-%7D-b.txt'],
	['http://example.com/a-%3F-b?c=d', 'http://example.com/a-%3F-b?c=d'],
	['http://example.com/a-%00-b?c=d', 'http://example.com/a-%00-b?c=d'],
];

foreach ($dataset as list($url, $expected)) {
	$url = new Url($url);
	$url->canonicalize();
	echo (string) $url, "\n\n";
	Assert::same($expected, (string) $url);
}


/*

path = path segments separated from each other by a "/"
path segment = URL unit except for / and ?
URL unit = URL code point or percent encoded bytes
URL code point =

                         0 to     20 C0 control characters
    21                  22 to     23 "#
    24                  25           %
    26 to     3B        3C           <
    3D                  3E           >
    3F to     5A        5B to     5E [\]^
    5F                  60           `
    61 to     7A        7B to     7D {|}
    7E                  7F to     9F C1 control characters
    A0 to   D7FF      D800 to   DFFF
  E000 to   FDCF      FDD0 to   FDEF
  FDF0 to   FFFD      FFFE to   FFFF
 10000 to  1FFFD     1FFFE to  1FFFF
 20000 to  2FFFD     2FFFE to  2FFFF
 30000 to  3FFFD     3FFFE to  3FFFF
 40000 to  4FFFD     4FFFE to  4FFFF
 50000 to  5FFFD     5FFFE to  5FFFF
 60000 to  6FFFD     6FFFE to  6FFFF
 70000 to  7FFFD     7FFFE to  7FFFF
 80000 to  8FFFD     8FFFE to  8FFFF
 90000 to  9FFFD     9FFFE to  9FFFF
 A0000 to  AFFFD     AFFFE to  AFFFF
 B0000 to  BFFFD     BFFFE to  BFFFF
 C0000 to  CFFFD     CFFFE to  CFFFF
 D0000 to  DFFFD     DFFFE to  DFFFF
 E0000 to  EFFFD     EFFFE to  EFFFF
 F0000 to  FFFFD     FFFFE to  FFFFF
100000 to 10FFFD    10FFFE to 10FFFF

!$&'()*+,-./:;=?@_~
21 24 26 27 28 29 2a 2b 2c 2d 2e 2f 3a 3b 3d 3f 40 5f 7e



Invalid:

00 01 02 03 04 05 06 07 08 09 0A 0B 0C 0D 0E 0F 10 11 12 13 14 15 16 17 18 19 1A 1B 1C 1D 1E 1F 20 22 23 25 2F 3C 3E 3F 5B 5C 5D 5E 60 7B 7C 7D

\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1A\x1B\x1C\x1D\x1E\x1F\x20\x22\x23\x25\x2F\x3C\x3E\x3F\x5B\x5C\x5D\x5E\x60\x7B\x7C\x7D
\x00\x01\x02\x03\x04\x05\x06\x07\x08\t\n\x0b\x0c\r\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f "#%/<>?[\\]^`{|}

http://vps.merxes.cz/%61-%01-%22-%23-%25-%3C-%3E-%5B-%5C-%5D-%5E-%60-%7B-%7C-%7D-%62.txt

IE: http://vps.merxes.cz/a-%01-%22-%23-%25-%3C-%3E-%5B-%5C-%5D-%5E-%60-%7B-%7C-%7D-b.txt
FF: http://vps.merxes.cz/a-%01-%22-%23-%25-%3C-%3E-%5B-%5C-%5D-%5E-%60-%7B-|-%7D-b.txt
Ch: http://vps.merxes.cz/a-%01-%22-%23-%25-%3C-%3E-%5B-%5C-%5D-%5E-%60-%7B-%7C-%7D-b.txt


*/
