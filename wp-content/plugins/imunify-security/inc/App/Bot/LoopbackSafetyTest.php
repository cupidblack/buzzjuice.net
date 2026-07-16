<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Bot;

/**
 * Post-deploy self-test: after the mu-plugin is installed, probe the
 * site's home URL and require an HTTP 200 response.
 *
 * The activation path calls run() immediately after install() returns
 * true. Any non-200 (network error, 5xx, timeout, WP_Error) is treated
 * as "the mu-plugin broke the site" — the activation path reverses by
 * calling MuPluginInstaller::uninstall() and surfaces the failure to
 * the admin.
 *
 * The HTTP probe is strictly synchronous and uses a short timeout so a
 * hung request cannot hold the activation handler indefinitely.
 *
 * @since 4.0.0
 */
class LoopbackSafetyTest {

	/**
	 * Connect + read timeout, in seconds. 5s is the Shield Security
	 * value; shorter timeouts trip on slow shared hosting where the WP
	 * bootstrap itself can take 2-3 seconds.
	 */
	const TIMEOUT_SECONDS = 5;

	/**
	 * Probe $home_url and return true iff it answers HTTP 200.
	 *
	 * @param string $home_url Site home URL to probe.
	 * @return bool True iff the URL returned HTTP 200.
	 */
	public function run( $home_url ) {
		if ( ! is_string( $home_url ) || '' === $home_url ) {
			return false;
		}
		$args     = array(
			'timeout'     => self::TIMEOUT_SECONDS,
			'redirection' => 2,
			'sslverify'   => false,
			// Tag the probe so logs can distinguish it from organic traffic.
			'user-agent'  => 'ImunifySecurity-Loopback/4.0',
		);
		$response = wp_remote_get( $home_url, $args );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		return 200 === (int) wp_remote_retrieve_response_code( $response );
	}
}
