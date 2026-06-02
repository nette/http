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


test('no header, no cookie returns false', function () {
	$request = new Http\Request(new Http\UrlScript);

	Assert::false($request->isFrom(FetchSite::SameOrigin));
	Assert::false($request->isFrom(FetchSite::CrossSite));
});


test('cookie fallback proves only "not cross-site"', function () {
	$request = new Http\Request(new Http\UrlScript, cookies: [
		Http\Helpers::StrictCookieName => '1',
	]);

	Assert::true($request->isFrom(FetchSite::SameOrigin));
	Assert::true($request->isFrom(FetchSite::SameSite));
	Assert::true($request->isFrom(FetchSite::None));
	Assert::true($request->isFrom([FetchSite::SameOrigin, FetchSite::CrossSite]));
	Assert::false($request->isFrom(FetchSite::CrossSite));
});


test('cookie fallback fails closed for dest & user', function () {
	$request = new Http\Request(new Http\UrlScript, cookies: [
		Http\Helpers::StrictCookieName => '1',
	]);

	// dest/user can't be proven by the cookie alone, so a stricter check must not pass
	Assert::false($request->isFrom(FetchSite::SameOrigin, FetchDest::Document));
	Assert::false($request->isFrom(FetchSite::SameOrigin, user: true));
	Assert::false($request->isFrom(FetchSite::SameOrigin, user: false));
});


test('cookie fallback not used when Sec-Fetch-Site present', function () {
	$request = new Http\Request(new Http\UrlScript, cookies: [
		Http\Helpers::StrictCookieName => '1',
	], headers: [
		'Sec-Fetch-Site' => 'cross-site',
	]);

	Assert::false($request->isFrom(FetchSite::SameOrigin));
	Assert::true($request->isFrom(FetchSite::CrossSite));
});


test('isSameSite() via Sec-Fetch-Site', function () {
	foreach (['same-origin', 'same-site'] as $site) {
		$request = new Http\Request(new Http\UrlScript, headers: ['Sec-Fetch-Site' => $site]);
		Assert::true($request->isSameSite(), $site);
	}

	foreach (['cross-site', 'none'] as $site) {
		$request = new Http\Request(new Http\UrlScript, headers: ['Sec-Fetch-Site' => $site]);
		Assert::false($request->isSameSite(), $site);
	}
});


test('isSameSite() via cookie fallback', function () {
	$request = new Http\Request(new Http\UrlScript, cookies: [
		Http\Helpers::StrictCookieName => '1',
	]);
	Assert::true($request->isSameSite());

	$request = new Http\Request(new Http\UrlScript);
	Assert::false($request->isSameSite());
});
