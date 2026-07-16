<?php
/**
 * Utilities: Logging API
 *
 * @package     AffiliateWP
 * @subpackage  Utilities
 * @copyright   Copyright (c) 2017, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
 */

#[\AllowDynamicProperties]

class Affiliate_WP_Logging {

	/**
	 * Get things started
	 *
	 * @since 1.7.15
	 */
	public function __construct() {
		$this->init();

		// Hook the migration method to the scheduled event.
		add_action( 'affwp_move_old_log_files', [ $this, 'move_old_log_files' ] );

		// Check if we need to schedule log migration.
		add_action( 'admin_init', [ $this, 'maybe_schedule_log_migration' ] );
	}

	/**
	 * Is the Log file Writeable?
	 *
	 * @since 2.24.1
	 * @var boolean
	 */
	public $is_writable = true;

	/**
	 * WordPress' Upload Directory
	 *
	 * @since 2.24.1
	 * @var string
	 */
	private string $upload_dir = ABSPATH;

	/**
	 * Our Upload Directory
	 *
	 * @since 2.24.1
	 * @var string
	 */
	private string $affwp_dir = ABSPATH;

	/**
	 * The Hash
	 *
	 * @since 2.24.1
	 * @var string
	 */
	private string $hash = '';

	/**
	 * The (Base) Filename of the Log.
	 *
	 * @since 2.24.1
	 * @var string
	 */
	private $filename = '';

	/**
	 * The full path to the log file.
	 *
	 * @since 2.24.1
	 * @var string
	 */
	private $file = '';

	/**
	 * Get things started
	 *
	 * @since 1.7.15
	 * @since 2.24.1 Updated to store logs in a more-secure place: uploads/affiliatewp.
	 * @return void
	 */
	public function init() {

		$this->upload_dir = untrailingslashit( wp_upload_dir( null, false )['basedir'] ?? ABSPATH );
		$this->hash       = affwp_get_hash( $this->upload_dir, affiliatewp_get_salt() );
		$this->filename   = sprintf( 'affwp-debug-log__%s.log', $this->hash );
		$this->affwp_dir  = "{$this->upload_dir}/affiliatewp";
		$this->file       = trailingslashit( $this->affwp_dir ) . $this->filename;

		if ( ! file_exists( $this->affwp_dir ) && wp_mkdir_p( $this->affwp_dir ) ) {
			@chmod( $this->affwp_dir, 0770 );
		}

		if ( ! file_exists( "{$this->affwp_dir}/index.php" ) ) {
			@file_put_contents( "{$this->affwp_dir}/index.php", '' );
		}

		if ( ! file_exists( "{$this->affwp_dir}/index.html" ) ) {
			@file_put_contents( "{$this->affwp_dir}/index.html", '' );
		}

		if ( ! is_writeable( $this->affwp_dir ) ) {
			$this->is_writable = false;
		}
	}

	/**
	 * Schedule the log file cleanup event.
	 *
	 * @since 2.24.1
	 * @since 2.30.0 Updated to use Action Scheduler instead of WP Cron.
	 */
	public static function schedule_log_migration() {

		// Check if Action Scheduler is available.
		if ( ! function_exists( 'as_has_scheduled_action' ) || ! function_exists( 'as_schedule_recurring_action' ) ) {
			return; // Action Scheduler not available.
		}

		$schedule_callback = function () {
			// Only schedule once.
			if ( as_has_scheduled_action( 'affwp_move_old_log_files', [], 'affiliatewp' ) ) {
				return;
			}

			// Schedule to run twice daily (every 12 hours).
			as_schedule_recurring_action( time(), 12 * HOUR_IN_SECONDS, 'affwp_move_old_log_files', [], 'affiliatewp' );
		};

		// If wp_loaded has already fired, run immediately. Otherwise, defer to wp_loaded.
		if ( did_action( 'wp_loaded' ) ) {
			$schedule_callback();
		} else {
			add_action( 'wp_loaded', $schedule_callback );
		}
	}

	/**
	 * Check if log migration needs to be scheduled.
	 *
	 * During plugin activation, Action Scheduler isn't fully initialized, so we flag
	 * that scheduling is needed and do it on the first admin_init after activation.
	 *
	 * @since 2.30.0
	 */
	public function maybe_schedule_log_migration() {
		// Check if we have the flag from activation.
		if ( ! get_option( 'affwp_needs_log_migration_schedule' ) ) {
			return;
		}

		// Clear the flag.
		delete_option( 'affwp_needs_log_migration_schedule' );

		// Now schedule the migration (Action Scheduler is ready now).
		self::schedule_log_migration();
	}

	/**
	 * Move any old log files to the new secure location.
	 *
	 * @since 2.24.1
	 */
	public function move_old_log_files() : void {

		foreach ( glob( "{$this->upload_dir}/affwp-debug-log*.log" ) as $old_log_file ) {

			$old_log_file_basename = basename( $old_log_file );

			// Copy the log file to the new location.
			if (

				// We failed to copy the file.
				! @copy( $old_log_file, "{$this->affwp_dir}/$old_log_file_basename" ) ||

				// Or that file does not exist in the new location.
				! file_exists( "{$this->affwp_dir}/$old_log_file_basename" )
			) {

				// Don't delete the old file.
				continue;
			}

			// Delete the insecure log file.
			@unlink( $old_log_file );
		}
	}

	/**
	 * Retrieve the log data
	 *
	 * @since 1.7.15
	 * @return string
	 */
	public function get_log() {
		return $this->get_file();
	}

	/**
	 * Log message to file
	 *
	 * @since 1.7.15
	 * @since 2.3 An optional `$data` parameter was added.
	 *
	 * @param string      $message Message to write to the debug log.
	 * @param array|mixed $data    Optional. Array of data or other output to send to the log.
	 *                             Default empty array.
	 * @return void
	 */
	public function log( $message, $data = [] ) {
		$message = date( 'Y-n-d H:i:s' ) . ' - ' . $message . "\r\n";

		if ( ! empty( $data ) ) {
			if ( is_array( $data ) ) {
				$data = var_export( $data, true );
			} elseif ( is_wp_error( $data ) ) {
				$data = $this->collate_errors( $data );
			} else {
				ob_start();

				var_dump( $data );

				$data = ob_get_clean();
			}

			$message .= $data;
		}

		$this->write_to_log( $message );
	}

	/**
	 * Retrieve the file data is written to
	 *
	 * @since 1.7.15
	 * @return string
	 */
	protected function get_file() {

		$file = '';

		if ( @file_exists( $this->file ) ) {

			if ( ! is_writeable( $this->file ) ) {
				$this->is_writable = false;
			}

			$file = @file_get_contents( $this->file );

		} else {

			@file_put_contents( $this->file, '' );
			@chmod( $this->file, 0664 );

		}

		return $file;
	}

	/**
	 * Write the log message
	 *
	 * @since 1.7.15
	 *
	 * @param string $message Message to write to the debug log.
	 * @return void
	 */
	protected function write_to_log( $message ) {
		$file  = $this->get_file();
		$file .= $message;

		@file_put_contents( $this->file, $file );
	}

	/**
	 * Write the log message
	 *
	 * @since 1.7.15
	 * @return void
	 */
	public function clear_log() {
		@unlink( $this->file );
	}

	/**
	 * Collates errors stored in a WP_Error object for output to the debug log.
	 *
	 * @since 2.3
	 *
	 * @param \WP_Error $wp_error WP_Error object.
	 * @return string Error log output. Empty if not a WP_Error object or if there are no errors to collate.
	 */
	public function collate_errors( $wp_error ) {
		$output = '';

		if ( ! is_wp_error( $wp_error ) ) {
			return $output;
		}

		$has_errors = method_exists( $wp_error, 'has_errors' ) ? $wp_error->has_errors() : ! empty( $wp_error->errors );

		if ( false === $has_errors ) {
			return $output;
		}

		foreach ( $wp_error->errors as $code => $messages ) {
			$message = implode( ' ', $messages );

			if ( isset( $wp_error->error_data[ $code ] ) ) {
				$data = $wp_error->error_data[ $code ];
			} else {
				$data = '';
			}

			$output .= sprintf( '- AffWP Error (%1$s): %2$s', $code, $message ) . "\r\n";

			if ( ! empty( $data ) ) {
				$output .= var_export( $data, true ) . "\r\n";
			}
		}

		return $output;
	}

	/**
	 * Retrieves the filesize of the log file.
	 *
	 * @since 2.5.4
	 *
	 * @param bool $formatted Whether to retrieve the formatted filesize. Default false.
	 * @return int|string Filesize in bytes or 0 if it doesn't exist. If `$formatted` is true,
	 *                    a formatted string.
	 */
	public function get_log_size( $formatted = false ) {
		$filesize = 0;

		if ( @file_exists( $this->file ) ) {
			$filesize = filesize( $this->file );
		}

		if ( true === $formatted ) {
			$filesize = size_format( $filesize, 2 );
		}

		return $filesize;
	}
}
