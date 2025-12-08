<?php
/**
 * Class for creating and updating custom DB tables.
 *
 * @package WPCode
 */

class WPCode_DB {

	/**
	 * Version of the db structure.
	 *
	 * @var string
	 */
	private $version = '1.0.1';

	/**
	 * The key used to store the version of the db in the options table.
	 *
	 * @var string
	 */
	private $version_key = 'wpcode_db_version';

	/**
	 * Run the SQL query to update/create the tables needed by WPCode.
	 *
	 * @return bool
	 */
	private function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'wpcode_revisions';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
				revision_id   BIGINT(20)  NOT NULL AUTO_INCREMENT,
				snippet_id	  BIGINT(20)  NOT NULL,
				revision_data LONGTEXT    NOT NULL,
				author_id     BIGINT(20)  NOT NULL,
				created    	  DATETIME    NOT NULL,
				remote_id     VARCHAR(20) NOT NULL,
				PRIMARY KEY  (revision_id),
				KEY snippet_id (snippet_id)
			) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		return empty( $wpdb->last_error );
	}

	/**
	 * Check if the version has changed and we need to update the table.
	 *
	 * @return void
	 */
	public function maybe_update_db() {
		$version = get_option( $this->version_key, '0' );

		if ( ! version_compare( $this->version, $version, '>' ) ) {
			return;
		}

		$updated = $this->create_table();

		if ( $updated ) {
			update_option( $this->version_key, $this->version, false );
		}
	}
}
