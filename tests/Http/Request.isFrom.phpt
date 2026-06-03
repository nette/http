<?php declare(strict_types=1);

use Nette\Http;
use Nette\Http\FetchDest;
use Nette\Http\FetchSite;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('matches Sec-Fetch-Site', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Sec-Fetch-Site' => 'same-origin',
	]);

	Assert::true($request->isFrom(FetchSite::SameOrigin));
	Assert::false($request->isFrom(FetchSite::CrossSite));
});


test('matches site & dest', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Sec-Fetch-Site' => 'same-origin',
		'Sec-Fetch-Dest' => 'document',
	]);

	Assert::true($request->isFrom(FetchSite::SameOrigin, FetchDest::Document));
	Assert::false($request->isFrom(FetchSite::SameOrigin, FetchDest::Empty));
});


test('matches site, dest & user', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Sec-Fetch-Site' => 'same-origin',
		'Sec-Fetch-Dest' => 'document',
		'Sec-Fetch-User' => '?1',
	]);

	Assert::true($request->isFrom(FetchSite::SameOrigin, FetchDest::Document, user: true));
	Assert::false($request->isFrom(FetchSite::SameOrigin, FetchDest::Document, user: false));
});


test('user-activation flag', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Sec-Fetch-Site' => 'same-origin',
		'Sec-Fetch-Dest' => 'empty',
	]);

	Assert::false($request->isFrom(FetchSite::SameOrigin, user: true));
	Assert::true($request->isFrom(FetchSite::SameOrigin, user: false));
});


test('fails when expected header missing', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Sec-Fetch-Site' => 'same-origin',
	]);

	Assert::false($request->isFrom(FetchSite::SameOrigin, FetchDest::Document));
});


test('accepts multiple expected values', function () {
	$request = new Http\Request(new Http\UrlScript, headers: [
		'Sec-Fetch-Site' => 'cross-site',
		'Sec-Fetch-Dest' => 'image',
	]);

	Assert::true($request->isFrom([FetchSite::SameOrigin, FetchSite::CrossSite], [FetchDest::Document, FetchDest::Image]));
	Assert::false($request->isFrom([FetchSite::CrossSite], [FetchDest::Document]));
	Assert::false($request->isFrom([FetchSite::SameOrigin], [FetchDest::Image]));
});


test('unknown header value matches nothing', function () {
	$request = new Http\Request(new Http\UrlScript, headers: ['Sec-Fetch-Site' => 'garbage']);
	Assert::false($request->isFrom(FetchSite::SameOrigin));
	Assert::false($request->isFrom(FetchSite::CrossSite));
});


test('no Sec-Fetch-Site returns false', function () {
	$request = new Http\Request(new Http\UrlScript);

	Assert::false($request->isFrom(FetchSite::SameOrigin));
	Assert::false($request->isFrom(FetchSite::CrossSite));
});
