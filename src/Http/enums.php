<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\Http;


/**
 * Values of the Sec-Fetch-Site request header, describing the relationship between
 * the page that initiated the request and the site that receives it.
 */
enum FetchSite: string
{
	/** the exact same origin (scheme, host, and port) */
	case SameOrigin = 'same-origin';

	/** the same site, possibly a different subdomain or scheme */
	case SameSite = 'same-site';

	/** a foreign site */
	case CrossSite = 'cross-site';

	/** the user initiated the request directly, e.g. by typing the URL or using a bookmark */
	case None = 'none';
}


/**
 * Values of the Sec-Fetch-Dest request header, describing the type of resource the
 * browser is fetching (a top-level navigation, an image, a script, a fetch/XHR call, ...).
 */
enum FetchDest: string
{
	case Audio = 'audio';
	case AudioWorklet = 'audioworklet';
	case Document = 'document';
	case Embed = 'embed';
	case Empty = 'empty';
	case FencedFrame = 'fencedframe';
	case Font = 'font';
	case Frame = 'frame';
	case Iframe = 'iframe';
	case Image = 'image';
	case Manifest = 'manifest';
	case Object = 'object';
	case PaintWorklet = 'paintworklet';
	case Report = 'report';
	case Script = 'script';
	case ServiceWorker = 'serviceworker';
	case SharedWorker = 'sharedworker';
	case Style = 'style';
	case Track = 'track';
	case Video = 'video';
	case WebIdentity = 'webidentity';
	case Worker = 'worker';
	case Xslt = 'xslt';
}


/**
 * Values of the SameSite cookie attribute.
 */
enum SameSite: string
{
	case Lax = 'Lax';
	case Strict = 'Strict';
	case None = 'None';
}
