<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fopen
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fwrite
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_fclose
 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_flock
 * phpcs:disable WordPress.WP.AlternativeFunctions.rename_rename
 * phpcs:disable WordPress.WP.AlternativeFunctions.unlink_unlink
 * phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export
 * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
 */

namespace CloudLinux\Imunify\App\Bot;

/**
 * Wp-cron-driven refresher for bundled bot-protection data.
 *
 * NOTE — Phase 1 ships *bundled* data only. The class is preserved
 * intact (specs, parsers, atomic writes, lock, sanitization) but
 * scheduleHooks() is deliberately not called from plugin bootstrap,
 * so the wp-cron events never fire and no overlay is ever produced.
 * BundledData therefore reads the snapshot shipped under
 * inc/App/Bot/data/ and nothing else. LifecycleHooks::onActivate()
 * additionally calls wp_clear_scheduled_hook() for both CRON_HOOK_*
 * names so any zombie events left by an earlier dev install get
 * dropped on activation. A future phase will turn this back on,
 * pointed at CloudLinux-owned mirrors of the
 * upstream sources rather than the upstreams themselves, with a
 * change-gate that requires human review when an incoming refresh
 * exceeds defined deltas (e.g. IP-range count, UA-token count,
 * checksum churn).
 *
 * Given a list of fetch specs (URL(s) + parser + output relpath + payload
 * type) the refresher downloads each source, parses it into a normalised
 * CIDR or signature list, and writes an OPcache-friendly PHP return-array
 * file into the overlay directory. BundledData picks up the overlay on
 * subsequent classification calls, transparently superseding the shipped
 * snapshot.
 *
 * Safety invariants:
 *   - Network failures and empty parse results keep the existing overlay
 *     file untouched (never block on transient failure).
 *   - Writes are atomic — each file is written to .tmp and rename'd into
 *     place so readers never observe a half-written overlay.
 *   - A POSIX advisory lock on <overlay>/.refresh.lock prevents two
 *     concurrently-firing wp-cron workers from racing each other.
 *
 * @since 4.0.0
 */
class SignatureRefresher {

	const CRON_HOOK_IP_RANGES  = 'imunify_security_bot_refresh_ip_ranges';
	const CRON_HOOK_SIGNATURES = 'imunify_security_bot_refresh_signatures';
	const LOCK_FILENAME        = '.refresh.lock';
	const TYPE_RANGES          = 'ranges';
	const TYPE_SIGNATURES      = 'signatures';
	const MIN_SIGNATURE_LENGTH = 4;
	const MAX_ITEMS_PER_SOURCE = 10000;
	const SIGNATURE_DENYLIST   = array(
		'mozilla',
		'chrome',
		'safari',
		'firefox',
		'edge',
		'opera',
		'msie',
		'trident',
		'applewebkit',
		'gecko',
		'webkit',
	);

	/**
	 * Absolute path to the overlay root.
	 *
	 * @var string
	 */
	private $overlay_dir;

	/**
	 * Injected HTTP client used to fetch source URLs.
	 *
	 * @var HttpClient
	 */
	private $http;

	/**
	 * Build a refresher bound to an overlay directory.
	 *
	 * @param string     $overlay_dir Absolute path to the overlay root.
	 * @param HttpClient $http        HTTP client (WpHttpClient in production, fake in tests).
	 */
	public function __construct( $overlay_dir, $http ) {
		$this->overlay_dir = rtrim( (string) $overlay_dir, '/' );
		$this->http        = $http;
	}

	/**
	 * Run a refresh pass over the given fetch specs.
	 *
	 * Each spec is an associative array with:
	 *   - 'relpath'  (string) output path under the overlay root
	 *   - 'urls'     (string[]) URL(s) to fetch; results are merged + deduped
	 *   - 'parser'   (callable) takes raw body string, returns array of items
	 *   - 'type'     (string) TYPE_RANGES or TYPE_SIGNATURES
	 *
	 * @param array $specs List of fetch specs.
	 */
	public function refresh( $specs ) {
		if ( ! is_array( $specs ) || empty( $specs ) ) {
			return;
		}

		$lock = $this->acquireLock();
		if ( null === $lock ) {
			return;
		}

		try {
			foreach ( $specs as $spec ) {
				// Isolate each spec so a single bad fetcher (e.g. a parser
				// callable that raises) cannot wipe out the rest of the
				// batch. The outer try/finally still guarantees the lock
				// is released on any exit path. reportFailOpenError
				// internally guards the do_action dispatch so a throwing
				// hook callback cannot propagate past this catch either.
				try {
					$this->refreshOne( $spec );
				} catch ( \Throwable $e ) {
					BundledData::reportFailOpenError( 'SignatureRefresher spec failed', $e->getMessage() );
				}
			}
		} finally {
			$this->releaseLock( $lock );
		}
	}

	/**
	 * Execute a single fetch spec.
	 *
	 * @param array $spec Fetch spec as documented on refresh().
	 */
	private function refreshOne( $spec ) {
		if ( ! is_array( $spec ) ) {
			return;
		}
		$relpath = isset( $spec['relpath'] ) ? (string) $spec['relpath'] : '';
		$urls    = isset( $spec['urls'] ) && is_array( $spec['urls'] ) ? $spec['urls'] : array();
		$parser  = isset( $spec['parser'] ) ? $spec['parser'] : null;
		$type    = isset( $spec['type'] ) ? $spec['type'] : self::TYPE_RANGES;

		if ( '' === $relpath || empty( $urls ) || ! is_callable( $parser ) ) {
			return;
		}

		$items = array();
		foreach ( $urls as $url ) {
			$body = $this->http->get( $url );
			if ( null === $body || '' === $body ) {
				return; // Any URL failure aborts the whole spec to avoid partial overlays.
			}
			$parsed = call_user_func( $parser, $body );
			if ( ! is_array( $parsed ) ) {
				return;
			}
			foreach ( $parsed as $item ) {
				if ( is_string( $item ) && '' !== $item ) {
					$items[] = $item;
				}
			}
		}

		$items = array_values( array_unique( $items ) );
		if ( empty( $items ) ) {
			return; // Parsed empty list → keep existing overlay as safer fallback.
		}

		if ( self::TYPE_SIGNATURES === $type ) {
			$items = self::sanitizeSignatures( $items );
			if ( empty( $items ) ) {
				BundledData::reportFailOpenError( 'SignatureRefresher', 'all fetched signatures rejected by sanitization' );
				return;
			}
		}

		if ( count( $items ) > self::MAX_ITEMS_PER_SOURCE ) {
			BundledData::reportFailOpenError(
				'SignatureRefresher',
				'source returned ' . count( $items ) . ' items, capped at ' . self::MAX_ITEMS_PER_SOURCE
			);
			$items = array_slice( $items, 0, self::MAX_ITEMS_PER_SOURCE );
		}

		$payload = array(
			'source_url' => implode( ', ', $urls ),
			'fetched_at' => gmdate( 'c' ),
			'checksum'   => 'sha256:' . hash( 'sha256', implode( "\n", $items ) ),
		);
		if ( self::TYPE_SIGNATURES === $type ) {
			$payload['signatures'] = $items;
		} else {
			$payload['ranges'] = $items;
		}

		$this->atomicWrite( $relpath, $payload );
	}

	/**
	 * Reject signature tokens that are too short or match common browser substrings.
	 *
	 * Public so `bin/update-bot-data.php` can apply the same rule when
	 * generating bundled data files, keeping bundled and cron-refreshed
	 * overlays byte-for-byte comparable.
	 *
	 * @param array $items Raw signature tokens.
	 * @return array Sanitized tokens, re-indexed.
	 */
	public static function sanitizeSignatures( $items ) {
		$out = array();
		foreach ( $items as $token ) {
			if ( strlen( $token ) < self::MIN_SIGNATURE_LENGTH ) {
				continue;
			}
			$lower = strtolower( $token );
			if ( in_array( $lower, self::SIGNATURE_DENYLIST, true ) ) {
				continue;
			}
			$out[] = $token;
		}
		return array_values( $out );
	}

	/**
	 * Atomically write a PHP return-array file to the overlay.
	 *
	 * @param string $relpath Relative path under the overlay root.
	 * @param array  $payload Payload array to serialise.
	 */
	private function atomicWrite( $relpath, $payload ) {
		if ( BundledData::isUnsafePath( $relpath ) ) {
			return;
		}
		$abs = $this->overlay_dir . '/' . ltrim( $relpath, '/' );
		$dir = dirname( $abs );
		if ( ! is_dir( $dir ) && ! mkdir( $dir, 0755, true ) && ! is_dir( $dir ) ) {
			BundledData::reportFailOpenError( 'atomicWrite', 'mkdir failed for ' . $dir );
			return;
		}

		$tmp = $abs . '.tmp';
		$h   = fopen( $tmp, 'w' );
		if ( false === $h ) {
			BundledData::reportFailOpenError( 'atomicWrite', 'fopen failed for ' . $tmp );
			return;
		}

		$contents = "<?php\n"
			. "// Auto-generated by SignatureRefresher. Do not edit by hand.\n\n"
			. 'return ' . var_export( $payload, true ) . ";\n";

		$bytes    = fwrite( $h, $contents );
		$expected = strlen( $contents );
		if ( false === $bytes || $bytes !== $expected ) {
			fclose( $h );
			@unlink( $tmp );
			BundledData::reportFailOpenError( 'atomicWrite', 'fwrite failed for ' . $tmp );
			return;
		}
		fclose( $h );

		if ( rename( $tmp, $abs ) ) {
			if ( function_exists( 'opcache_invalidate' ) ) {
				@opcache_invalidate( $abs, true );
			}
		} else {
			@unlink( $tmp );
			BundledData::reportFailOpenError( 'atomicWrite', 'rename failed for ' . $abs );
		}
	}

	/**
	 * Acquire the refresher's POSIX advisory lock.
	 *
	 * @return resource|null Lock file handle on success, null when another worker holds the lock.
	 */
	private function acquireLock() {
		if ( ! is_dir( $this->overlay_dir ) && ! mkdir( $this->overlay_dir, 0755, true ) && ! is_dir( $this->overlay_dir ) ) {
			return null;
		}
		$path = $this->overlay_dir . '/' . self::LOCK_FILENAME;
		$h    = fopen( $path, 'c+' );
		if ( false === $h ) {
			return null;
		}
		if ( ! flock( $h, LOCK_EX | LOCK_NB ) ) {
			fclose( $h );
			return null;
		}
		return $h;
	}

	/**
	 * Release a previously-acquired lock handle.
	 *
	 * @param resource $handle File handle returned by acquireLock().
	 */
	private function releaseLock( $handle ) {
		flock( $handle, LOCK_UN );
		fclose( $handle );
	}

	/**
	 * Register the daily wp-cron events driving this refresher.
	 *
	 * Phase 1 deliberately does NOT call this from plugin bootstrap —
	 * the plugin ships bundled data only. A future phase will turn
	 * cron-driven refresh back on, pointed at owned mirrors
	 * with a change-gate. Until then the events stay unscheduled.
	 *
	 * The unit test for this method still exercises the wiring so the
	 * Phase-2 re-enable lands cleanly.
	 */
	public static function scheduleHooks() {
		if ( ! wp_next_scheduled( self::CRON_HOOK_IP_RANGES ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK_IP_RANGES );
		}
		if ( ! wp_next_scheduled( self::CRON_HOOK_SIGNATURES ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK_SIGNATURES );
		}
	}
}
