Nette HTTP Component
====================

[![Downloads this Month](https://img.shields.io/packagist/dm/nette/http.svg)](https://packagist.org/packages/nette/http)
[![Tests](https://github.com/nette/http/workflows/Tests/badge.svg?branch=master)](https://github.com/nette/http/actions)
[![Build Status Windows](https://ci.appveyor.com/api/projects/status/github/nette/http?branch=master&svg=true)](https://ci.appveyor.com/project/dg/http/branch/master)
[![Coverage Status](https://coveralls.io/repos/github/nette/http/badge.svg?branch=master)](https://coveralls.io/github/nette/http?branch=master)
[![Latest Stable Version](https://poser.pugx.org/nette/http/v/stable)](https://github.com/nette/http/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/nette/http/blob/master/license.md)


Introduction
------------

HTTP request and response are encapsulated in `Nette\Http\Request` and `Nette\Http\Response` objects which offer comfortable API and also act as
sanitization filter.

Documentation can be found on the [website](https://doc.nette.org/http-request-response).


[Support Me](https://github.com/sponsors/dg)
--------------------------------------------

Do you like Nette DI? Are you looking forward to the new features?

[![Buy me a coffee](https://files.nette.org/icons/donation-3.svg)](https://github.com/sponsors/dg)

Thank you!


Installation
------------

```shell
composer require nette/http
```

It requires PHP version 8.1 and supports PHP up to 8.4.


HTTP Request
============

An HTTP request is an [Nette\Http\Request](https://api.nette.org/3.0/Nette/Http/Request.html) object. What is important is that Nette when [creating](#RequestFactory) this object, it clears all GET, POST and COOKIE input parameters as well as URLs of control characters and invalid UTF-8 sequences. So you can safely continue working with the data. The cleaned data is then used in presenters and forms.

Class `Request` is immutable. It has no setters, it has only one so-called wither `withUrl()`, which does not change the object, but returns a new instance with a modified value.


withUrl(Nette\Http\UrlScript $url): Nette\Http\Request
------------------------------------------------------
Returns a clone with a different URL.

getUrl(): Nette\Http\UrlScript
------------------------------
Returns the URL of the request as object [UrlScript|urls#UrlScript].

```php
$url = $httpRequest->getUrl();
echo $url; // https://nette.org/en/documentation?action=edit
echo $url->getHost(); // nette.org
```

Browsers do not send a fragment to the server, so `$url->getFragment()` will return an empty string.

getQuery(string $key = null): string|array|null
-----------------------------------------------
Returns GET request parameters:

```php
$all = $httpRequest->getQuery();    // array of all URL parameters
$id = $httpRequest->getQuery('id'); // returns GET parameter 'id' (or null)
```

getPost(string $key = null): string|array|null
----------------------------------------------
Returns POST request parameters:

```php
$all = $httpRequest->getPost();     // array of all POST parameters
$id = $httpRequest->getPost('id');  // returns POST parameter 'id' (or null)
```

getFile(string $key): Nette\Http\FileUpload|array|null
------------------------------------------------------
Returns [upload](#Uploaded-Files) as object [Nette\Http\FileUpload](https://api.nette.org/3.0/Nette/Http/FileUpload.html):

```php
$file = $httpRequest->getFile('avatar');
if ($file->hasFile()) { // was any file uploaded?
	$file->getName(); // name of the file sent by user
	$file->getSanitizedName(); // the name without dangerous characters
}
```

getFiles(): array
-----------------
Returns tree of [upload files](#Uploaded-Files) in a normalized structure, with each leaf an instance of [Nette\Http\FileUpload](https://api.nette.org/3.0/Nette/Http/FileUpload.html):

```php
$files = $httpRequest->getFiles();
```

getCookie(string $key): string|array|null
-----------------------------------------
Returns a cookie or `null` if it does not exist.

```php
$sessId = $httpRequest->getCookie('sess_id');
```

getCookies(): array
-------------------
Returns all cookies:

```php
$cookies = $httpRequest->getCookies();
```

getMethod(): string
-------------------
Returns the HTTP method with which the request was made.

```php
echo $httpRequest->getMethod(); // GET, POST, HEAD, PUT
```

isMethod(string $method): bool
------------------------------
Checks the HTTP method with which the request was made. The parameter is case-insensitive.

```php
if ($httpRequest->isMethod('GET')) ...
```

getHeader(string $header): ?string
----------------------------------
Returns an HTTP header or `null` if it does not exist. The parameter is case-insensitive:

```php
$userAgent = $httpRequest->getHeader('User-Agent');
```

getHeaders(): array
-------------------
Returns all HTTP headers as associative array:

```php
$headers = $httpRequest->getHeaders();
echo $headers['Content-Type'];
```

getReferer(): ?Nette\Http\UrlImmutable
--------------------------------------
What URL did the user come from? Beware, it is not reliable at all.

isSecured(): bool
-----------------
Is the connection encrypted (HTTPS)? You may need to [set up a proxy|configuring#HTTP proxy] for proper functionality.

isSameSite(): bool
------------------
Is the request coming from the same (sub) domain and is initiated by clicking on a link?

isAjax(): bool
--------------
Is it an AJAX request?

getRemoteAddress(): ?string
---------------------------
Returns the user's IP address. You may need to [set up a proxy|configuring#HTTP proxy] for proper functionality.

getRemoteHost(): ?string
------------------------
Returns DNS translation of the user's IP address. You may need to [set up a proxy|configuring#HTTP proxy] for proper functionality.

getRawBody(): ?string
---------------------
Returns the body of the HTTP request:

```php
$body = $httpRequest->getRawBody();
```

detectLanguage(array $langs): ?string
-------------------------------------
Detects language. As a parameter `$lang`, we pass an array of languages ​​that the application supports, and it returns the one preferred by browser. It is not magic, the method just uses the `Accept-Language` header. If no match is reached, it returns `null`.

```php
// Header sent by browser: Accept-Language: cs,en-us;q=0.8,en;q=0.5,sl;q=0.3

$langs = ['hu', 'pl', 'en']; // languages supported in application
echo $httpRequest->detectLanguage($langs); // en
```



RequestFactory
--------------

The object of the current HTTP request is created by [Nette\Http\RequestFactory](https://api.nette.org/3.0/Nette/Http/RequestFactory.html). If you are writing an application that does not use a DI container, you create a request as follows:

```php
$factory = new Nette\Http\RequestFactory;
$httpRequest = $factory->fromGlobals();
```

RequestFactory can be configured before calling `fromGlobals()`. We can disable all sanitization of input parameters from invalid UTF-8 sequences using `$factory->setBinary()`. And also set up a proxy server, which is important for the correct detection of the user's IP address using `$factory->setProxy(...)`.

It's possible to clean up URLs from characters that can get into them because of poorly implemented comment systems on various other websites by using filters:

```php
// remove spaces from path
$requestFactory->urlFilters['path']['%20'] = '';

// remove dot, comma or right parenthesis form the end of the URL
$requestFactory->urlFilters['url']['[.,)]$'] = '';

// clean the path from duplicated slashes (default filter)
$requestFactory->urlFilters['path']['/{2,}'] = '/';
```



HTTP Response
=============

An HTTP response is an [Nette\Http\Response](https://api.nette.org/3.0/Nette/Http/Response.html) object. Unlike the [Request](#HTTP-Request), the object is mutable, so you can use setters to change the state, ie to send headers. Remember that all setters **must be called before any actual output is sent.** The `isSent()` method tells if output have been sent. If it returns `true`, each attempt to send a header throws an `Nette\InvalidStateException` exception.


setCode(int $code, string $reason = null)
-----------------------------------------
Changes a status [response code](https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10). For better source code readability it is recommended to use [predefined constants](https://api.nette.org/3.0/Nette/Http/IResponse.html) instead of actual numbers.

```php
$httpResponse->setCode(Nette\Http\Response::S404_NotFound);
```

getCode(): int
--------------
Returns the status code of the response.

isSent(): bool
--------------
Returns whether headers have already been sent from the server to the browser, so it is no longer possible to send headers or change the status code.

setHeader(string $name, string $value)
--------------------------------------
Sends an HTTP header and **overwrites** previously sent header of the same name.

```php
$httpResponse->setHeader('Pragma', 'no-cache');
```

addHeader(string $name, string $value)
--------------------------------------
Sends an HTTP header and **doesn't overwrite** previously sent header of the same name.

```php
$httpResponse->addHeader('Accept', 'application/json');
$httpResponse->addHeader('Accept', 'application/xml');
```

deleteHeader(string $name)
--------------------------
Deletes a previously sent HTTP header.

getHeader(string $header): ?string
----------------------------------
Returns the sent HTTP header, or `null` if it does not exist. The parameter is case-insensitive.

```php
$pragma = $httpResponse->getHeader('Pragma');
```

getHeaders(): array
-------------------
Returns all sent HTTP headers as associative array.

```php
$headers = $httpResponse->getHeaders();
echo $headers['Pragma'];
```

setContentType(string $type, string $charset = null)
----------------------------------------------------
Sends the header `Content-Type`.

```php
$httpResponse->setContentType('text/plain', 'UTF-8');
```

redirect(string $url, int $code = self::S302_FOUND): void
---------------------------------------------------------
Redirects to another URL. Don't forget to quit the script then.

```php
$httpResponse->redirect('http://example.com');
exit;
```

setExpiration(?string $time)
----------------------------
Sets the expiration of the HTTP document using the `Cache-Control` and `Expires` headers. The parameter is either a time interval (as text) or `null`, which disables caching.

```php
// browser cache expires in one hour
$httpResponse->setExpiration('1 hour');
```

setCookie(string $name, string $value, $time, string $path = null, string $domain = null, bool $secure = null, bool $httpOnly = null, string $sameSite = null)
--------------------------------------------------------------------------------------------------------------------------------------------------------------
Sends a cookie. The default values ​​of the parameters are:
- `$path` with scope to all directories (`'/'`)
- `$domain` with scope of the current (sub)domain, but not its subdomains
- `$secure` defaults to false
- `$httpOnly` is true, so the cookie is inaccessible to JavaScript
- `$sameSite` is null, so the flag is not specified

The time can be specified as a string or the number of seconds.

```php
$httpResponse->setCookie('lang', 'en', '100 days');
```

deleteCookie(string $name, string $path = null, string $domain = null, bool $secure = null): void
-------------------------------------------------------------------------------------------------
Deletes a cookie. The default values ​​of the parameters are:
- `$path` with scope to all directories (`'/'`)
- `$domain` with scope of the current (sub)domain, but not its subdomains
- `$secure` defaults to false

```php
$httpResponse->deleteCookie('lang');
```


Uploaded Files
==============

Method `Nette\Http\Request::getFiles()` return a tree of upload files in a normalized structure, with each leaf an instance of `Nette\Http\FileUpload`. These objects encapsulate the data submitted by the `<input type=file>` form element.

The structure reflects the naming of elements in HTML. In the simplest example, this might be a single named form element submitted as:

```html
<input type="file" name="avatar">
```

In this case, the `$request->getFiles()` returns array:

```php
[
	'avatar' => /* FileUpload instance */
]
```

The `FileUpload` object is created even if the user did not upload any file or the upload failed. Method `hasFile()` returns true if a file has been sent:

```php
$request->getFile('avatar')->hasFile();
```

In the case of an input using array notation for the name:

```html
<input type="file" name="my-form[details][avatar]">
```

returned tree ends up looking like this:

```php
[
	'my-form' => [
		'details' => [
			'avatar' => /* FileUpload instance */
		],
	],
]
```

You can also create arrays of files:

```html
<input type="file" name="my-form[details][avatars][] multiple">
```

In such a case structure looks like:

```php
[
	'my-form' => [
		'details' => [
			'avatars' => [
				0 => /* FileUpload instance */,
				1 => /* FileUpload instance */,
				2 => /* FileUpload instance */,
			],
		],
	],
]
```

The best way to access index 1 of a nested array is as follows:

```php
$file = Nette\Utils\Arrays::get(
	$request->getFiles(),
	['my-form', 'details', 'avatars', 1],
	null
);
if ($file instanceof FileUpload) {
	...
}
```

Because you can't trust data from the outside and therefore don't rely on the form of the file structure, it's safer to use the `Arrays::get()` than the `$request->getFiles()['my-form']['details']['avatars'][1]`, which may fail.


Overview of `FileUpload` Methods .{toc: FileUpload}
---------------------------------------------------

hasFile(): bool
---------------
Returns `true` if the user has uploaded a file.

isOk(): bool
------------
Returns `true` if the file was uploaded successfully.

getError(): int
---------------
Returns the error code associated with the uploaded file. It is be one of [UPLOAD_ERR_XXX](http://php.net/manual/en/features.file-upload.errors.php) constants. If the file was uploaded successfully, it returns `UPLOAD_ERR_OK`.

move(string $dest)
------------------
Moves an uploaded file to a new location. If the destination file already exists, it will be overwritten.

```php
$file->move('/path/to/files/name.ext');
```

getContents(): ?string
----------------------
Returns the contents of the uploaded file. If the upload was not successful, it returns `null`.

getContentType(): ?string
-------------------------
Detects the MIME content type of the uploaded file based on its signature. If the upload was not successful or the detection failed, it returns `null`.

Requires PHP extension `fileinfo`.

getName(): string
-----------------
Returns the original file name as submitted by the browser.

Do not trust the value returned by this method. A client could send a malicious filename with the intention to corrupt or hack your application.

getSanitizedName(): string
--------------------------
Returns the sanitized file name. It contains only ASCII characters `[a-zA-Z0-9.-]`. If the name does not contain such characters, it returns 'unknown'. If the file is JPEG, PNG, GIF, or WebP image, it returns the correct file extension.

getSize(): int
--------------
Returns the size of the uploaded file. If the upload was not successful, it returns `0`.

getTemporaryFile(): string
--------------------------
Returns the path of the temporary location of the uploaded file. If the upload was not successful, it returns `''`.

isImage(): bool
---------------
Returns `true` if the uploaded file is a JPEG, PNG, GIF, or WebP image. Detection is based on its signature. The integrity of the entire file is not checked. You can find out if an image is not corrupted for example by trying to [load it](#toImage).

Requires PHP extension `fileinfo`.

getImageSize(): ?array
----------------------
Returns a pair of `[width, height]` with dimensions of the uploaded image. If the upload was not successful or is not a valid image, it returns `null`.

toImage(): Nette\Utils\Image
----------------------------
Loads an image as an `Image` object. If the upload was not successful or is not a valid image, it throws an `Nette\Utils\ImageException` exception.



Sessions
========

When using sessions, each user receives a unique identifier called session ID, which is passed in a cookie. This serves as the key to the session data. Unlike cookies, which are stored on the browser side, session data is stored on the server side.

The session is managed by the [Nette\Http\Session](https://api.nette.org/3.0/Nette/Http/Session.html) object.


Starting Session
----------------

By default, Nette automatically starts a session if the HTTP request contains a cookie with a session ID. It also starts automatically when we start reading from or writing data to it. Manually is session started by `$session->start()`.

PHP sends HTTP headers affecting caching when starting the session, see `session_cache_limiter`, and possibly a cookie with the session ID. Therefore, it is always necessary to start the session before sending any output to the browser, otherwise an exception will be thrown. So if you know that a session will be used during page rendering, start it manually before, for example in the presenter.

In developer mode, Tracy starts the session because it uses it to display redirection and AJAX requests bars in the Tracy Bar.


Section
-------

In pure PHP, the session data store is implemented as an array accessible via a global variable `$_SESSION`. The problem is that applications normally consist of a number of independent parts, and if all have only one same array available, sooner or later a name collision will occur.

Nette Framework solves the problem by dividing the entire space into sections (objects [Nette\Http\SessionSection](https://api.nette.org/3.0/Nette/Http/SessionSection.html)). Each unit then uses its own section with a unique name and no collisions can occur.

We get the section from the session manager:

```php
$section = $session->getSession('unique name');
```

In the presenter it is enough to call `getSession()` with the parameter:

```php
// $this is Presenter
$section = $this->getSession('unique name');
```

The existence of the section can be checked by the method `$session->hasSection('unique name')`.

And then it's really simple to work with that section:

```php
// variable writing
$section->userName = 'john'; // nebo $section['userName'] = 'john';

// variable reading
echo $section->userName; // nebo echo $section['userName'];

// variable removing
unset($section->userName);  // unset($section['userName']);
```

It's possible to use `foreach` cycle to obtain all variables from section:

```php
foreach ($section as $key => $val) {
	echo "$key = $val";
}
```

Accessing a non-existent variable does not generate any error (the returned value is null). It could be undesirable behavior in some cases and that's why there is a possibility to change it:

```php
$section->warnOnUndefined = true;
```


How to Set Expiration
---------------------

Expiration can be set for individual sections or even individual variables. We can let the user's login expire in 20 minutes, but still remember the contents of a shopping cart.

```php
// section will expire after 20 minutes
$section->setExpiration('20 minutes');

// variable $section->flash will expire after 30 seconds
$section->setExpiration('30 seconds', 'flash');
```

The cancellation of the previously set expiration can be achieved by the method `removeExpiration()`. Immediate deletion of the whole section will be ensured by the method `remove()`.



Session Management
------------------

Overview of methods of the `Nette\Http\Session` class for session management:

start(): void
-------------
Starts a session.

isStarted(): bool
-----------------
Is the session started?

close(): void
-------------
Ends the session. The session ends automatically at the end of the script.

destroy(): void
---------------
Ends and deletes the session.

exists(): bool
--------------
Does the HTTP request contain a cookie with a session ID?

regenerateId(): void
--------------------
Generates a new random session ID. Data remain unchanged.

getId(): string
---------------
Returns the session ID.


Configuration
-------------

Methods must be called before starting a session.

setName(string $name): static
-----------------------------
Changes the session name. It is possible to run several different sessions at the same time within one website, each under a different name.

getName(): string
-----------------
Returns the session name.

setOptions(array $options): static
----------------------------------
Configures the session. It is possible to set all PHP [session directives](https://www.php.net/manual/en/session.configuration.php) (in camelCase format, eg write `savePath` instead of `session.save_path`) and also [readAndClose](https://www.php.net/manual/en/function.session-start.php#refsect1-function.session-start-parameters).

setExpiration(?string $time): static
------------------------------------
Sets the time of inactivity after which the session expires.

setCookieParameters(string $path, string $domain = null, bool $secure = null, string $samesite = null): static
--------------------------------------------------------------------------------------------------------------
Sets parameters for cookies.

setSavePath(string $path): static
---------------------------------
Sets the directory where session files are stored.

setHandler(\SessionHandlerInterface $handler): static
-----------------------------------------------------
Sets custom handler, see [PHP documentation](https://www.php.net/manual/en/class.sessionhandlerinterface.php).


Safety First
------------

The server assumes that it communicates with the same user as long as requests contain the same session ID. The task of security mechanisms is to ensure that this behavior really works and that there is no possibility to substitute or steal an identifier.

That's why Nette Framework properly configures PHP directives to transfer session ID only in cookies, to avoid access from JavaScript and to ignore the identifiers in the URL. Moreover in critical moments, such as user login, it generates a new Session ID.

Function ini_set is used for configuring PHP, but unfortunately, its use is prohibited at some web hosting services. If it's your case, try to ask your hosting provider to allow this function for you, or at least to configure his server properly.  .[note]



Url
===

The [Nette\Http\Url](https://api.nette.org/3.0/Nette/Http/Url.html) class makes it easy to work with the URL and its individual components, which are outlined in this diagram:

```
 scheme  user  password  host   port    path        query  fragment
   |      |      |        |      |       |            |       |
 /--\   /--\ /------\ /-------\ /--\/----------\ /--------\ /----\
 http://john:xyz%2A12@nette.org:8080/en/download?name=param#footer
 \______\__________________________/
     |               |
  hostUrl        authority
```

URL generation is intuitive:

```php
use Nette\Http\Url;

$url = new Url;
$url->setScheme('https')
	->setHost('localhost')
	->setPath('/edit')
	->setQueryParameter('foo', 'bar');

echo $url; // 'https://localhost/edit?foo=bar'
```

You can also parse the URL and then manipulate it:

```php
$url = new Url(
	'http://john:xyz%2A12@nette.org:8080/en/download?name=param#footer'
);
```

The following methods are available to get or change individual URL components:

Setter									| Getter						| Returned value
----------------------------------------|-------------------------------|------------------
`setScheme(string $scheme)`				| `getScheme(): string`			| `'http'`
`setUser(string $user)`					| `getUser(): string`			| `'john'`
`setPassword(string $password)`			| `getPassword(): string`		| `'xyz*12'`
`setHost(string $host)`					| `getHost(): string`			| `'nette.org'`
`setPort(int $port)`						| `getPort(): ?int`				| `8080`
`setPath(string $path)`					| `getPath(): string`			| `'/en/download'`
`setQuery(string\|array $query)`			| `getQuery(): string`			| `'name=param'`
`setFragment(string $fragment)`			| `getFragment(): string`		| `'footer'`
--											| `getAuthority(): string`		| `'nette.org:8080'`
--											| `getHostUrl(): string`		| `'http://nette.org:8080'`
--											| `getAbsoluteUrl(): string` 	| full URL

We can also operate with individual query parameters using:

Setter									| Getter
----------------------------------------|---------
`setQuery(string\|array $query)`  		| `getQueryParameters(): array`
`setQueryParameter(string $name, $val)`	| `getQueryParameter(string $name)`

Method `getDomain(int $level = 2)` returns the right or left part of the host. This is how it works if the host is `www.nette.org`:

Usage									| Result
----------------------------------------|---------
`getDomain(1)`  |  `'org'`
`getDomain(2)`  |  `'nette.org'`
`getDomain(3)`  |  `'www.nette.org'`
`getDomain(0)`  |  `'www.nette.org'`
`getDomain(-1)` |  `'www.nette'`
`getDomain(-2)` |  `'www'`
`getDomain(-3)` |  `''`


The `Url` class implements the `JsonSerializable` interface and has a `__toString()` method so that the object can be printed or used in data passed to `json_encode()`.

```php
echo $url;
echo json_encode([$url]);
```

Method `isEqual(string|Url $anotherUrl): bool` tests whether the two URLs are identical.

```php
$url->isEqual('https://nette.org');
```


UrlImmutable
============

The class [Nette\Http\UrlImmutable](https://api.nette.org/3.0/Nette/Http/UrlImmutable.html) is an immutable alternative to class `Url` (just as in PHP `DateTimeImmutable` is immutable alternative to `DateTime`). Instead of setters, it has so-called withers, which do not change the object, but return new instances with a modified value:

```php
use Nette\Http\UrlImmutable;

$url = new UrlImmutable(
	'http://john:xyz%2A12@nette.org:8080/en/download?name=param#footer'
);

$newUrl = $url
	->withUser('')
	->withPassword('')
	->withPath('/cs/');

echo $newUrl; // 'http://nette.org:8080/cs/?name=param#footer'
```

The following methods are available to get or change individual URL components:

Wither									| Getter						| Returned value
----------------------------------------|-------------------------------|------------------
`withScheme(string $scheme)`				| `getScheme(): string`			| `'http'`
`withUser(string $user)`					| `getUser(): string`			| `'john'`
`withPassword(string $password)`			| `getPassword(): string`		| `'xyz*12'`
`withHost(string $host)`					| `getHost(): string`			| `'nette.org'`
`withPort(int $port)`						| `getPort(): ?int`				| `8080`
`withPath(string $path)`					| `getPath(): string`			| `'/en/download'`
`withQuery(string\|array $query)`			| `getQuery(): string`			| `'name=param'`
`withFragment(string $fragment)`			| `getFragment(): string`		| `'footer'`
--											| `getAuthority(): string`		| `'nette.org:8080'`
--											| `getHostUrl(): string`		| `'http://nette.org:8080'`
--											| `getAbsoluteUrl(): string` 	| full URL

We can also operate with individual query parameters using:

Wither								| Getter
------------------------------------|---------
`withQuery(string\|array $query)` 	| `getQueryParameters(): array`
--										| `getQueryParameter(string $name)`

The `getDomain(int $level = 2)` method works the same as the method in `Url`. Method `withoutUserInfo()` removes `user` and `password`.

The `UrlImmutable` class implements the `JsonSerializable` interface and has a `__toString()` method so that the object can be printed or used in data passed to `json_encode()`.

```php
echo $url;
echo json_encode([$url]);
```

Method `isEqual(string|Url $anotherUrl): bool` tests whether the two URLs are identical.


If you like Nette, **[please make a donation now](https://github.com/sponsors/dg)**. Thank you!
