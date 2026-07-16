<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Bot;

/**
 * Honeypot URI and its supporting fragments.
 *
 * The link is rendered invisibly in every page footer and banned in
 * robots.txt. A well-behaved visitor (human or compliant crawler) will
 * never request it, so any hit is a zero-false-positive malicious-bot
 * signal. The Pipeline passes the result of isTriggered() to the
 * Classifier, which short-circuits to MALICIOUS_BOT / 403.
 *
 * Everything on this class is static and pure — no filesystem, no WP —
 * so the Pipeline's hot path can call isTriggered() without worrying
 * about side effects.
 *
 * @since 4.0.0
 */
class Honeypot {

	/**
	 * URI path that triggers the honeypot. Hard-coded rather than
	 * configurable so bundled robots.txt guidance stays in lockstep with
	 * detection. Changing the value is a breaking change for any
	 * site-level robots.txt an admin may have published.
	 */
	const PATH = '/imunify-bot-check';

	/**
	 * Honeypot URI.
	 *
	 * @return string
	 */
	public static function path() {
		return self::PATH;
	}

	/**
	 * Whether the supplied REQUEST_URI targets the honeypot.
	 *
	 * Matching is case-insensitive on the leading path component so a
	 * bot that normalises the URI to upper-case still trips the trap.
	 * Query strings and a trailing slash are ignored, but the token must
	 * be the entire first path segment — a benign page that happens to
	 * contain "imunify-bot-check" deeper in the path does not match.
	 *
	 * @param string $uri Request URI (from $_SERVER['REQUEST_URI']).
	 * @return bool
	 */
	public static function isTriggered( $uri ) {
		if ( ! is_string( $uri ) || '' === $uri ) {
			return false;
		}
		// Collapse leading double slashes so parse_url does not treat the
		// path as a host in a protocol-relative URL (e.g. "//imunify-bot-check").
		if ( 0 === strpos( $uri, '//' ) ) {
			$uri = '/' . ltrim( $uri, '/' );
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pre-WP hot path; wp_parse_url may not be available.
		$path = (string) parse_url( $uri, PHP_URL_PATH );
		$path = rtrim( $path, '/' );
		return 0 === strcasecmp( $path, self::PATH );
	}

	/**
	 * Hidden footer link that feeds the trap.
	 *
	 * Inline CSS keeps the link invisible even if the active theme strips
	 * stylesheets. aria-hidden + tabindex=-1 keep screen readers and
	 * keyboard users away from it, so only a bot crawling the DOM and
	 * following every href will reach it.
	 *
	 * @return string HTML snippet suitable for echoing inside wp_footer.
	 */
	public static function footerLinkHtml() {
		$href = htmlspecialchars( self::PATH, ENT_QUOTES, 'UTF-8' );
		return '<a href="' . $href . '" '
			. 'rel="nofollow" aria-hidden="true" tabindex="-1" '
			. 'style="display:none!important;position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden">'
			. 'imunify-bot-check</a>';
	}

	/**
	 * Robots.txt fragment that bans compliant crawlers from the honeypot.
	 *
	 * Appended to the site robots.txt via the `robots_txt` filter so any
	 * bot that claims to honour robots.txt but requests this path is
	 * definitively lying.
	 *
	 * @return string
	 */
	public static function robotsTxtFragment() {
		return "User-agent: *\nDisallow: " . self::PATH . "\n";
	}
}
