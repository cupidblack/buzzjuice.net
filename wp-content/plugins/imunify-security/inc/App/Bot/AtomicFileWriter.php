<?php
/**
 * Copyright (с) Cloud Linux GmbH & Cloud Linux Software, Inc 2010-2025 All Rights Reserved
 *
 * Licensed under CLOUD LINUX LICENSE AGREEMENT
 * https://www.cloudlinux.com/legal/
 */

namespace CloudLinux\Imunify\App\Bot;

/**
 * Write-temp-then-rename helper shared by MuPluginInstaller and BotSettingsWriter.
 *
 * @since 4.0.1
 */
class AtomicFileWriter {

	/**
	 * Write $body to $target via a temp file + rename so concurrent
	 * readers never see a partially-written file.
	 *
	 * @param string   $target Absolute destination path.
	 * @param string   $body   File bytes.
	 * @param int|null $mode   Optional chmod applied to the temp file
	 *                         before rename (e.g. 0440). Null skips.
	 * @return bool True iff the file exists at $target after the call.
	 */
	public static function write( $target, $body, $mode = null ) {
		$tmp = $target . '.tmp-' . getmypid();

		// @phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen
		$handle = fopen( $tmp, 'w' );
		if ( false === $handle ) {
			return false;
		}
		// @phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		$bytes = fwrite( $handle, $body );
		// @phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		fclose( $handle );

		if ( false === $bytes || strlen( $body ) !== $bytes ) {
			// @phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@unlink( $tmp );
			return false;
		}

		if ( null !== $mode ) {
			// @phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@chmod( $tmp, $mode );
		}

		if ( ! rename( $tmp, $target ) ) {
			// @phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@unlink( $tmp );
			return false;
		}

		if ( function_exists( 'opcache_invalidate' ) ) {
			// @phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			@opcache_invalidate( $target, true );
		}

		clearstatcache( true, $target );
		return is_file( $target );
	}
}
