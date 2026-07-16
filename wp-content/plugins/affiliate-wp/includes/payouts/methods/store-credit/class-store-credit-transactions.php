<?php
/**
 * Store Credit Transactions Database Class
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/StoreCredit
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

namespace AffiliateWP\Core\Store_Credit\Transactions;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store Credit Transactions class.
 *
 * @since 2.29.0
 */
class DB extends \Affiliate_WP_DB {

	/**
	 * Table name.
	 *
	 * @since 2.29.0
	 * @var string
	 */
	public $table_name = 'affiliate_wp_store_credit_transactions';

	/**
	 * Cache group for this table.
	 *
	 * @since 2.29.0
	 * @var string
	 */
	public $cache_group = 'store_credit';

	/**
	 * Primary key column name.
	 *
	 * @since 2.29.0
	 * @var string
	 */
	public $primary_key = 'transaction_id';

	/**
	 * Database version.
	 *
	 * @since 2.29.0
	 * @var string
	 */
	public $version = '1.0';

	/**
	 * Constructor.
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name = $wpdb->prefix . $this->table_name;

		// Check if table needs to be created
		$this->maybe_create_table();
	}

	/**
	 * Get columns.
	 *
	 * @since 2.29.0
	 * @return array
	 */
	public function get_columns() {
		return [
			'transaction_id' => '%d',
			'movement'       => '%s',
			'type'           => '%s',
			'from'           => '%s',
			'to'             => '%s',
			'time'           => '%s',
			'for_user_id'    => '%d',
			'by_user_id'     => '%d',
			'reference_id'   => '%d',
			'note'           => '%s',
		];
	}

	/**
	 * Get default column values.
	 *
	 * @since 2.29.0
	 * @return array
	 */
	public function get_column_defaults() {
		return [
			'transaction_id' => 0,
			'movement'       => 'increase',
			'type'           => 'unknown',
			'from'           => 0.00,
			'to'             => 0.00,
			'time'           => gmdate( 'Y-m-d H:i:s' ),
			'for_user_id'    => 0,
			'by_user_id'     => 0,
			'reference_id'   => 0,
			'note'           => '',
		];
	}

	/**
	 * Create the table.
	 *
	 * @since 2.29.0
	 */
	public function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$this->table_name} (
			`transaction_id` bigint(20) NOT NULL AUTO_INCREMENT,
			`movement`       mediumtext NOT NULL,
			`type`           mediumtext NOT NULL,
			`from`           mediumtext NOT NULL,
			`to`             mediumtext NOT NULL,
			`time`           datetime NOT NULL,
			`for_user_id`    bigint(20) NOT NULL,
			`by_user_id`     bigint(20) NOT NULL,
			`reference_id`   bigint(20) NOT NULL,
			`note`           mediumtext NOT NULL,
			PRIMARY KEY      (`transaction_id`)
		) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

	/**
	 * Check if the table needs to be created.
	 *
	 * @since 2.29.0
	 */
	private function maybe_create_table() {
		global $wpdb;

		// Check if table exists
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" );

		if ( ! $table_exists ) {
			$this->create_table();
		}
	}

	/**
	 * Get transactions.
	 *
	 * @since 2.29.0
	 *
	 * @param array $args Query arguments.
	 * @param bool  $count Whether to return a count.
	 * @return array|int Array of transaction objects or count.
	 */
	public function get_transactions( $args = [], $count = false ) {
		global $wpdb;

		$defaults = [
			'number'      => 20,
			'offset'      => 0,
			'for_user_id' => 0,
			'orderby'     => 'time',
			'order'       => 'DESC',
			'search'      => '',
			'type'        => '',
			'fields'      => '',
		];

		$args = wp_parse_args( $args, $defaults );

		// Backward compat: set default number
		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = ' WHERE 1=1';

		// User ID
		if ( ! empty( $args['for_user_id'] ) ) {
			$where .= $wpdb->prepare( ' AND for_user_id = %d', $args['for_user_id'] );
		}

		// Transaction type
		if ( ! empty( $args['type'] ) ) {
			$where .= $wpdb->prepare( ' AND type = %s', $args['type'] );
		}

		// Search
		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where .= $wpdb->prepare( ' AND (reference LIKE %s)', $search );
		}

		// Order
		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'transaction_id' : $args['orderby'];
		$args['orderby'] = sanitize_text_field( $args['orderby'] );
		$args['order']   = strtoupper( $args['order'] );
		$args['order']   = in_array( $args['order'], [ 'ASC', 'DESC' ], true ) ? $args['order'] : 'DESC';

		$orderby = $args['orderby'] . ' ' . $args['order'];

		// Fields
		$fields = '*';
		if ( ! empty( $args['fields'] ) ) {
			if ( is_array( $args['fields'] ) ) {
				$fields = implode( ',', array_map( 'sanitize_key', $args['fields'] ) );
			} else {
				$fields = sanitize_text_field( $args['fields'] );
			}
		}

		// Clauses
		$clauses          = compact( 'fields', 'where', 'orderby', 'count' );
		$clauses['join']  = '';
		$clauses['order'] = '';

		$results = $this->get_results( $clauses, $args );

		return $results;
	}

	/**
	 * Get transactions for a specific user.
	 *
	 * @since 2.29.0
	 *
	 * @param int   $user_id User ID.
	 * @param array $args Additional arguments.
	 * @return array|false Array of transaction objects or false on failure.
	 */
	public function get_transactions_for_user( $user_id, $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'for_user_id' => $user_id,
				'orderby'     => 'time',
				'order'       => 'DESC',
				'number'      => 100,
			]
		);

		return $this->get_transactions( $args );
	}

	/**
	 * Add a new transaction.
	 *
	 * @since 2.29.0
	 *
	 * @param array $data Transaction data.
	 * @return int|false Transaction ID on success, false on failure.
	 */
	public function add_transaction( $data ) {
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		// Validate required fields
		if ( empty( $data['for_user_id'] ) || empty( $data['type'] ) || empty( $data['movement'] ) ) {
			return false;
		}

		return $this->insert( $data, 'transaction' );
	}

	/**
	 * Update a transaction.
	 *
	 * @since 2.29.0
	 *
	 * @param int   $transaction_id Transaction ID.
	 * @param array $data Transaction data.
	 * @return bool True on success, false on failure.
	 */
	public function update_transaction( $transaction_id, $data ) {
		return $this->update( $transaction_id, $data );
	}

	/**
	 * Delete a transaction.
	 *
	 * @since 2.29.0
	 *
	 * @param int $transaction_id Transaction ID.
	 * @return bool True on success, false on failure.
	 */
	public function delete_transaction( $transaction_id ) {
		return $this->delete( $transaction_id );
	}

	/**
	 * Get transaction by ID.
	 *
	 * @since 2.29.0
	 *
	 * @param int $transaction_id Transaction ID.
	 * @return object|false Transaction object or false on failure.
	 */
	public function get_transaction( $transaction_id ) {
		return $this->get_by( 'transaction_id', $transaction_id );
	}
}

/**
 * Main Transactions class.
 *
 * @since 2.29.0
 */
class Transactions {

	/**
	 * Database instance.
	 *
	 * @since 2.29.0
	 * @var DB
	 */
	public $db;

	/**
	 * Constructor.
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		$this->db = new DB();
	}

	/**
	 * Get transactions for a user.
	 *
	 * @since 2.29.0
	 *
	 * @param int   $user_id User ID.
	 * @param array $args Additional arguments.
	 * @return array|false
	 */
	public function get_transactions_for_user( $user_id, $args = [] ) {
		return $this->db->get_transactions_for_user( $user_id, $args );
	}

	/**
	 * Add a transaction.
	 *
	 * @since 2.29.0
	 *
	 * @param array $data Transaction data.
	 * @return int|false
	 */
	public function add( $data ) {
		return $this->db->add_transaction( $data );
	}

	/**
	 * Update a transaction.
	 *
	 * @since 2.29.0
	 *
	 * @param int   $transaction_id Transaction ID.
	 * @param array $data Transaction data.
	 * @return bool
	 */
	public function update( $transaction_id, $data ) {
		return $this->db->update_transaction( $transaction_id, $data );
	}

	/**
	 * Delete a transaction.
	 *
	 * @since 2.29.0
	 *
	 * @param int $transaction_id Transaction ID.
	 * @return bool
	 */
	public function delete( $transaction_id ) {
		return $this->db->delete_transaction( $transaction_id );
	}

	/**
	 * Get a transaction by ID.
	 *
	 * @since 2.29.0
	 *
	 * @param int $transaction_id Transaction ID.
	 * @return object|false
	 */
	public function get( $transaction_id ) {
		return $this->db->get_transaction( $transaction_id );
	}
}
