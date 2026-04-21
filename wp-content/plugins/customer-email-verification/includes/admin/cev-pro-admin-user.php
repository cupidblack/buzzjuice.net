<?php
/**
 * Admin User Management Class for Customer Email Verification
 *
 * Handles all user-related admin functionality including user list columns,
 * user profile fields, bulk actions, filtering, and AJAX handlers for managing
 * email verification status of users in the WordPress admin area.
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Load AJAX response helper class.
if ( ! class_exists( 'CEV_Ajax_Response' ) ) {
	$plugin_instance = cev_pro();
	if ( $plugin_instance ) {
		$plugin_path = $plugin_instance->get_plugin_path();
		require_once $plugin_path . '/includes/class-cev-ajax-response.php';
	}
}

if ( ! class_exists( 'CEV_Pro_Admin_User' ) ) {

	/**
	 * CEV_Pro_Admin_User Class
	 *
	 * Manages user-related admin functionality for the Customer Email Verification plugin.
	 * This includes custom columns in user lists, user profile fields, bulk actions,
	 * filtering capabilities, and AJAX handlers for verification operations.
	 */
	class CEV_Pro_Admin_User {

		/**
		 * Admin instance for accessing shared methods.
		 *
		 * @var WC_Customer_Email_Verification_Admin_Pro
		 */
		private $admin;

		/**
		 * Instance of this class (Singleton pattern).
		 *
		 * @var CEV_Pro_Admin_User
		 */
		private static $instance;

		/**
		 * User meta key for email verification status.
		 *
		 * @var string
		 */
		const VERIFICATION_META_KEY = 'customer_email_verified';

		/**
		 * User meta key for verification code.
		 *
		 * @var string
		 */
		const VERIFICATION_CODE_META_KEY = 'customer_email_verification_code';

		/**
		 * User meta key for verification PIN.
		 *
		 * @var string
		 */
		const VERIFICATION_PIN_META_KEY = 'cev_email_verification_pin';

		/**
		 * Database table name for user logs.
		 *
		 * @var string
		 */
		const USER_LOG_TABLE = 'cev_user_log';

		/**
		 * Nonce action for email verification.
		 *
		 * @var string
		 */
		const NONCE_ACTION_EMAIL = 'wc_cev_email';

		/**
		 * Nonce action for user deletion.
		 *
		 * @var string
		 */
		const NONCE_ACTION_DELETE = 'delete_user_nonce';

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @param WC_Customer_Email_Verification_Admin_Pro|null $admin Admin instance.
		 */
		public function __construct( $admin = null ) {
			$this->admin = $admin;
			$this->init();
		}

		/**
		 * Get the singleton instance of this class.
		 *
		 * Ensures that only one instance of the class is created throughout
		 * the WordPress request lifecycle (Singleton pattern).
		 *
		 * @since 1.0.0
		 * @param WC_Customer_Email_Verification_Admin_Pro|null $admin Admin instance.
		 * @return CEV_Pro_Admin_User The single instance of this class.
		 */
		public static function get_instance( $admin = null ) {
			if ( null === self::$instance ) {
				self::$instance = new self( $admin );
			}
			return self::$instance;
		}

		/**
		 * Get the path to a view template file.
		 *
		 * @since 1.0.0
		 * @param string $template_name The name of the template file (without .php extension).
		 * @return string The full path to the template file.
		 */
		private function get_view_path( $template_name ) {
			$plugin_instance = cev_pro();
			$plugin_path     = $plugin_instance->get_plugin_path();
			return $plugin_path . '/includes/admin/views/' . $template_name . '.php';
		}

		/**
		 * Initialize hooks and filters.
		 *
		 * Registers all WordPress hooks and filters needed for user management
		 * functionality. Only registers user list features if email verification is enabled.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function init() {
			// Check if email verification is enabled.
			$cev_enable_email_verification = cev_pro()->function->cev_pro_admin_settings( 'cev_enable_email_verification', 0 );

			if ( 1 === (int) $cev_enable_email_verification ) {
				$this->register_user_list_hooks();
			}

			// Register AJAX handlers for user log deletion (always available).
			$this->register_ajax_hooks();
		}

		/**
		 * Register hooks for user list customization.
		 *
		 * Adds filters and actions for custom columns, user profiles, filtering,
		 * bulk actions, and manual verification.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function register_user_list_hooks() {
			// Add custom columns to user list.
			add_filter( 'manage_users_columns', array( $this, 'add_column_users_list' ), 10, 1 );
			add_filter( 'manage_users_custom_column', array( $this, 'add_details_in_custom_users_list' ), 10, 3 );

			// Display verification fields in user profiles.
			add_action( 'show_user_profile', array( $this, 'show_cev_fields_in_single_user' ) );
			add_action( 'edit_user_profile', array( $this, 'show_cev_fields_in_single_user' ) );

			// Manual verification/unverification handler.
			add_action( 'admin_head', array( $this, 'cev_manual_verify_user' ) );

			// User filtering.
			add_action( 'restrict_manage_users', array( $this, 'filter_user_by_verified' ) );
			add_filter( 'pre_get_users', array( $this, 'filter_users_by_user_by_verified_section' ) );

			// Bulk actions.
			add_filter( 'bulk_actions-users', array( $this, 'add_custom_bulk_actions_for_user' ) );
			add_filter( 'handle_bulk_actions-users', array( $this, 'users_bulk_action_handler' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'user_bulk_action_notices' ) );

			// AJAX handler for manual verification from user menu.
			add_action( 'wp_ajax_cev_manualy_user_verify_in_user_menu', array( $this, 'cev_manualy_user_verify_in_user_menu' ) );
		}

		/**
		 * Register AJAX hooks for user log deletion.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function register_ajax_hooks() {
			add_action( 'wp_ajax_delete_user', array( $this, 'cev_delete_user' ), 4 );
			add_action( 'wp_ajax_delete_users', array( $this, 'cev_delete_users' ), 4 );
		}

		/**
		 * Handle deletion of a single user log entry via AJAX.
		 *
		 * Verifies nonce, validates input, and deletes the specified log entry
		 * from the database. Returns JSON response indicating success or failure.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function cev_delete_user() {
			try {
				// Verify nonce for security.
				if ( ! check_ajax_referer( self::NONCE_ACTION_DELETE, 'nonce', false ) ) {
					CEV_Ajax_Response::send_nonce_error();
					return;
				}

				// Validate and sanitize input.
				if ( ! isset( $_POST['id'] ) || empty( $_POST['id'] ) ) {
					CEV_Ajax_Response::send_error(
						'missing_field',
						__( 'Invalid request. Missing user ID.', 'customer-email-verification' ),
						array( 'field' => 'id' )
					);
					return;
				}

				global $wpdb;

				// Sanitize and validate the log entry ID.
				$log_id     = absint( $_POST['id'] );
				$table_name = $wpdb->prefix . self::USER_LOG_TABLE;

				// Validate log ID.
				if ( $log_id <= 0 ) {
					CEV_Ajax_Response::send_error(
						'invalid_field',
						__( 'Invalid user ID provided.', 'customer-email-verification' ),
						array( 'field' => 'id' )
					);
					return;
				}

				// Attempt to delete the log entry.
				$result = $wpdb->delete(
					$table_name,
					array( 'id' => $log_id ),
					array( '%d' )
				);

				// Respond with success or failure.
				if ( false !== $result ) {
					CEV_Ajax_Response::send_success(
						array( 'deleted' => true, 'id' => $log_id ),
						__( 'User log deleted successfully.', 'customer-email-verification' ),
						'log_deleted'
					);
				} else {
					CEV_Ajax_Response::send_error(
						'delete_failed',
						__( 'Failed to delete user log.', 'customer-email-verification' ),
						array( 'id' => $log_id )
					);
				}
			} catch ( Exception $e ) {
				error_log( 'CEV Admin User Delete Error: ' . $e->getMessage() );
				CEV_Ajax_Response::send_server_error( $e->getMessage() );
			}
		}

		/**
		 * Handle deletion of multiple user log entries via AJAX.
		 *
		 * Verifies nonce, validates input, and deletes multiple log entries
		 * from the database. Returns JSON response with success/failure counts.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function cev_delete_users() {
			try {
				// Verify nonce for security.
				if ( ! check_ajax_referer( self::NONCE_ACTION_DELETE, 'nonce', false ) ) {
					CEV_Ajax_Response::send_nonce_error();
					return;
				}

				global $wpdb;
				$table_name = $wpdb->prefix . self::USER_LOG_TABLE;

				// Initialize results tracking.
				$success_count = 0;
				$failure_count = 0;
				$messages      = array();
				$deleted_ids   = array();
				$failed_ids    = array();

				// Validate and sanitize input.
				if ( ! isset( $_POST['ids'] ) || ! is_array( $_POST['ids'] ) || empty( $_POST['ids'] ) ) {
					CEV_Ajax_Response::send_error(
						'missing_field',
						__( 'Invalid or missing IDs.', 'customer-email-verification' ),
						array( 'field' => 'ids' )
					);
					return;
				}

				// Sanitize all IDs.
				$ids = array_map( 'absint', $_POST['ids'] );

				foreach ( $ids as $id ) {
					// Skip invalid IDs.
					if ( $id <= 0 ) {
						$failure_count++;
						$failed_ids[] = $id;
						$messages[]   = sprintf(
							/* translators: %d: Log entry ID */
							__( 'Invalid ID: %d', 'customer-email-verification' ),
							$id
						);
						continue;
					}

					// Attempt to delete the log entry.
					$result = $wpdb->delete(
						$table_name,
						array( 'id' => $id ),
						array( '%d' )
					);

					if ( false !== $result ) {
						$success_count++;
						$deleted_ids[] = $id;
					} else {
						$failure_count++;
						$failed_ids[] = $id;
						$messages[]   = sprintf(
							/* translators: %d: Log entry ID */
							__( 'Failed to delete ID: %d', 'customer-email-verification' ),
							$id
						);
					}
				}

				// Prepare response data.
				$response_data = array(
					'successCount'  => $success_count,
					'failureCount'  => $failure_count,
					'deletedIds'    => $deleted_ids,
					'failedIds'     => $failed_ids,
					'messages'      => $messages,
				);

				// Determine response message.
				if ( 0 === $failure_count ) {
					$message = sprintf(
						/* translators: %d: Number of deleted entries */
						_n( '%d log entry deleted successfully.', '%d log entries deleted successfully.', $success_count, 'customer-email-verification' ),
						$success_count
					);
					$code = 'all_deleted';
				} elseif ( $success_count > 0 ) {
					$message = sprintf(
						/* translators: %1$d: Success count, %2$d: Failure count */
						__( '%1$d deleted, %2$d failed.', 'customer-email-verification' ),
						$success_count,
						$failure_count
					);
					$code = 'partial_delete';
				} else {
					$message = __( 'Failed to delete log entries.', 'customer-email-verification' );
					$code    = 'delete_failed';
				}

				// Send response.
				if ( 0 === $failure_count ) {
					CEV_Ajax_Response::send_success( $response_data, $message, $code );
				} else {
					CEV_Ajax_Response::send_error( $code, $message, $response_data );
				}
			} catch ( Exception $e ) {
				error_log( 'CEV Admin Users Delete Error: ' . $e->getMessage() );
				CEV_Ajax_Response::send_server_error( $e->getMessage() );
			}
		}

		/**
		 * Add custom columns to the user listing screen.
		 *
		 * Adds 'Email Verification' and 'Actions' columns to the WordPress
		 * users list table in the admin area.
		 *
		 * @since 1.0.0
		 * @param array $columns Existing columns in the user listing screen.
		 * @return array Modified columns with custom entries.
		 */
		public function add_column_users_list( $columns ) {
			$columns['cev_verified'] = __( 'Email Verification', 'customer-email-verification' );
			$columns['cev_action']   = __( 'Actions', 'customer-email-verification' );

			return $columns;
		}

		/**
		 * Add custom values to the custom columns in the user listing screen.
		 *
		 * Renders the content for the 'Email Verification' and 'Actions' columns
		 * based on the user's verification status. Enqueues necessary scripts and styles.
		 *
		 * @since 1.0.0
		 * @param string $val        The current column content.
		 * @param string $column_name The name of the column being rendered.
		 * @param int    $user_id    The ID of the user for the current row.
		 * @return string HTML content to be displayed in the custom columns.
		 */
		public function add_details_in_custom_users_list( $val, $column_name, $user_id ) {
			// Enqueue required assets.
			$this->enqueue_user_admin_assets();

			// Get user verification status.
			$verified = get_user_meta( $user_id, self::VERIFICATION_META_KEY, true );

			// Render content for 'Email Verification' column.
			if ( 'cev_verified' === $column_name ) {
				return $this->render_verification_status_column( $user_id, $verified );
			}

			// Render content for 'Actions' column.
			if ( 'cev_action' === $column_name ) {
				return $this->render_actions_column( $user_id, $verified );
			}

			return $val;
		}

		/**
		 * Enqueue scripts and styles for user admin interface.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		private function enqueue_user_admin_assets() {
			$plugin_instance = cev_pro();
			$plugin_version   = $plugin_instance->version;
			$plugin_url      = $plugin_instance->plugin_dir_url();

			wp_enqueue_script( 'wc-jquery-blockui' );
			wp_enqueue_style(
				'customer_email_verification_user_admin_styles',
				$plugin_url . 'assets/css/user-admin.css',
				array(),
				$plugin_version
			);
			wp_enqueue_script(
				'customer_email_verification_user_admin_script',
				$plugin_url . 'assets/js/user-admin.js',
				array( 'jquery', 'wp-util' ),
				$plugin_version,
				true
			);
		}

		/**
		 * Render verification status column content.
		 *
		 * @since 1.0.0
		 * @param int    $user_id  User ID.
		 * @param string $verified Verification status.
		 * @return string HTML content.
		 */
		private function render_verification_status_column( $user_id, $verified ) {
			$is_admin_user = $this->is_admin_user( $user_id );

			$template_path = $this->get_view_path( 'user/verification-status-column' );
			if ( file_exists( $template_path ) ) {
				ob_start();
				include $template_path;
				return ob_get_clean();
			}
			return '-';
		}

		/**
		 * Render actions column content.
		 *
		 * @since 1.0.0
		 * @param int    $user_id  User ID.
		 * @param string $verified Verification status.
		 * @return string HTML content.
		 */
		private function render_actions_column( $user_id, $verified ) {
			$is_admin_user = $this->is_admin_user( $user_id );
			$nonce_action_email = self::NONCE_ACTION_EMAIL;

			$template_path = $this->get_view_path( 'user/actions-column' );
			if ( file_exists( $template_path ) ) {
				ob_start();
				include $template_path;
				return ob_get_clean();
			}
			return '';
		}

		/**
		 * Display customer email verification fields in the single user profile.
		 *
		 * Adds a custom section to the user profile edit page showing verification
		 * status and action buttons for manual verification, unverification, and resending emails.
		 *
		 * @since 1.0.0
		 * @param WP_User $user The user object of the current profile being edited.
		 * @return void
		 */
		public function show_cev_fields_in_single_user( $user ) {
			// Enqueue required assets.
			$this->enqueue_user_admin_assets();

			$user_id  = absint( $user->ID );
			$verified = get_user_meta( $user_id, self::VERIFICATION_META_KEY, true );
			$nonce    = wp_create_nonce( self::NONCE_ACTION_EMAIL );

			// Skip admin users.
			$is_admin_user = $this->is_admin_user( $user_id );
			$verification_meta_key = self::VERIFICATION_META_KEY;
			$nonce_action_email = self::NONCE_ACTION_EMAIL;

			$template_path = $this->get_view_path( 'user/user-profile-fields' );
			if ( file_exists( $template_path ) ) {
				include $template_path;
			}
		}

		/**
		 * Manually verify or un-verify a user's email from the admin area.
		 *
		 * Handles GET requests from admin links to verify/unverify users or
		 * resend verification emails. Processes nonce verification and updates
		 * user meta accordingly.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function cev_manual_verify_user() {
			// Handle verification/unverification.
			if ( $this->is_verification_request_valid() ) {
				$user_id = isset( $_GET['user_id'] ) ? absint( wc_clean( $_GET['user_id'] ) ) : 0;

				if ( 0 === $user_id ) {
					return;
				}

				if ( isset( $_GET['wc_cev_confirm'] ) && 'true' === $_GET['wc_cev_confirm'] ) {
					// Mark user as verified.
					update_user_meta( $user_id, self::VERIFICATION_META_KEY, 'true' );
					$this->trigger_delay_new_account_email( $user_id );
					add_action( 'admin_notices', array( $this, 'manual_cev_verify_email_success_admin' ) );
				} else {
					// Mark user as unverified.
					$unverify_user_id = isset( $_GET['user_id'] ) ? absint( wc_clean( $_GET['user_id'] ) ) : 0;
					if ( 0 !== $unverify_user_id ) {
						delete_user_meta( $unverify_user_id, self::VERIFICATION_META_KEY );
						add_action( 'admin_notices', array( $this, 'manual_cev_verify_email_unverify_admin' ) );
					}
				}
			}

		}

		/**
		 * Check if verification request is valid.
		 *
		 * @since 1.0.0
		 * @return bool True if valid, false otherwise.
		 */
		private function is_verification_request_valid() {
			return isset( $_GET['user_id'], $_GET['wp_nonce'] ) &&
				   wp_verify_nonce( wc_clean( $_GET['wp_nonce'] ), self::NONCE_ACTION_EMAIL );
		}


		/**
		 * Add dropdown filter for user verification status.
		 *
		 * Adds a filter dropdown on the Users listing page to filter users
		 * by their email verification status (verified or non-verified).
		 *
		 * @since 1.0.0
		 * @param string $which The position of the filter ("top" or "bottom").
		 * @return void
		 */
		public function filter_user_by_verified( $which ) {
			$template_path = $this->get_view_path( 'user/user-filter-dropdown' );
			if ( file_exists( $template_path ) ) {
				include $template_path;
			}
		}

		/**
		 * Filter users by email verification status in the admin Users list.
		 *
		 * Modifies the user query to filter users based on their verification status
		 * when a filter is selected in the admin users list.
		 *
		 * @since 1.0.0
		 * @param WP_User_Query $query The current user query.
		 * @return void
		 */
		public function filter_users_by_user_by_verified_section( $query ) {
			global $pagenow;

			// Only apply on admin users page.
			if ( ! is_admin() || 'users.php' !== $pagenow ) {
				return;
			}

			// Get selected filter value.
			$top_filter     = isset( $_GET['customer_email_verified_top'] ) ? wc_clean( $_GET['customer_email_verified_top'] ) : '';
			$bottom_filter  = isset( $_GET['customer_email_verified_bottom'] ) ? wc_clean( $_GET['customer_email_verified_bottom'] ) : '';
			$selected_value = ! empty( $top_filter ) ? $top_filter : $bottom_filter;

			if ( empty( $selected_value ) ) {
				return;
			}

			$meta_query = array();

			if ( 'true' === $selected_value ) {
				// Filter for verified users.
				$meta_query[] = array(
					'key'     => self::VERIFICATION_META_KEY,
					'value'   => 'true',
					'compare' => 'LIKE',
				);
			} else {
				// Filter for non-verified users.
				$meta_query = array(
					'relation' => 'AND',
					array(
						'key'     => self::VERIFICATION_PIN_META_KEY,
						'compare' => 'EXISTS',
					),
					array(
						'key'     => self::VERIFICATION_META_KEY,
						'compare' => 'NOT EXISTS',
					),
				);
			}

			// Apply the meta query to the user query.
			$query->set( 'meta_query', $meta_query );
		}

		/**
		 * Add custom bulk actions to the Users list.
		 *
		 * Adds "Verify users email" and "Send verification email" options
		 * to the bulk actions dropdown in the users list.
		 *
		 * @since 1.0.0
		 * @param array $bulk_array The existing bulk actions for users.
		 * @return array The updated bulk actions including custom actions.
		 */
		public function add_custom_bulk_actions_for_user( $bulk_array ) {
			$bulk_array['verify_users_email']      = __( 'Verify users email', 'customer-email-verification' );

			return $bulk_array;
		}

		/**
		 * Handle custom bulk actions for verifying users and sending verification emails.
		 *
		 * Processes bulk actions selected in the users list, updates user meta,
		 * sends emails, and redirects with success messages.
		 *
		 * @since 1.0.0
		 * @param string $redirect   The redirect URL after the bulk action.
		 * @param string $doaction   The selected bulk action.
		 * @param array  $object_ids The array of user IDs to perform the action on.
		 * @return string The updated redirect URL with action-specific query parameters.
		 */
		public function users_bulk_action_handler( $redirect, $doaction, $object_ids ) {
			// Clean redirect URL.
			$redirect = remove_query_arg(
				array( 'user_id', 'wc_cev_confirm', 'wp_nonce', 'verify_users_emails' ),
				$redirect
			);

			// Handle 'verify_users_email' bulk action.
			if ( 'verify_users_email' === $doaction ) {
				foreach ( $object_ids as $user_id ) {
					update_user_meta( absint( $user_id ), self::VERIFICATION_META_KEY, 'true' );
				}
				$redirect = add_query_arg( 'verify_users_emails', count( $object_ids ), $redirect );
			}

			return $redirect;
		}

		/**
		 * Display admin notices for custom bulk actions on users.
		 *
		 * Shows success messages after bulk actions are performed, indicating
		 * how many users were affected.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function user_bulk_action_notices() {
			$template_path = $this->get_view_path( 'user/bulk-action-notices' );
			if ( file_exists( $template_path ) ) {
				include $template_path;
			}
		}

		/**
		 * Handle manual user verification actions via AJAX.
		 *
		 * Processes AJAX requests to verify, unverify, or resend verification emails
		 * for users from the admin user menu.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function cev_manualy_user_verify_in_user_menu() {
			try {
				// Verify nonce.
				if ( ! isset( $_POST['wp_nonce'] ) || ! wp_verify_nonce( wc_clean( $_POST['wp_nonce'] ), self::NONCE_ACTION_EMAIL ) ) {
					CEV_Ajax_Response::send_nonce_error();
					return;
				}

				// Validate and sanitize input.
				$user_id     = isset( $_POST['id'] ) ? absint( wc_clean( $_POST['id'] ) ) : 0;
				$action_type = isset( $_POST['actin_type'] ) ? sanitize_text_field( wp_unslash( $_POST['actin_type'] ) ) : ''; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- Keeping original for backward compatibility.

				if ( $user_id <= 0 ) {
					CEV_Ajax_Response::send_error(
						'invalid_field',
						__( 'Invalid user ID provided.', 'customer-email-verification' ),
						array( 'field' => 'id' )
					);
					return;
				}

				if ( empty( $action_type ) ) {
					CEV_Ajax_Response::send_error(
						'missing_field',
						__( 'Action type is required.', 'customer-email-verification' ),
						array( 'field' => 'actin_type' )
					);
					return;
				}

				// Validate user exists.
				$user = get_user_by( 'id', $user_id );
				if ( ! $user ) {
					CEV_Ajax_Response::send_error(
						'user_not_found',
						__( 'User not found.', 'customer-email-verification' ),
						array( 'userId' => $user_id )
					);
					return;
				}

				// Process action.
				switch ( $action_type ) {
					case 'unverify_user':
						$this->handle_unverify_user_action( $user_id );
						break;

					case 'verify_user':
						$this->handle_verify_user_action( $user_id );
						break;

					default:
						CEV_Ajax_Response::send_error(
							'invalid_action',
							__( 'Invalid action type.', 'customer-email-verification' ),
							array( 'actionType' => $action_type )
						);
						break;
				}
			} catch ( Exception $e ) {
				error_log( 'CEV Admin Manual Verify Error: ' . $e->getMessage() );
				CEV_Ajax_Response::send_server_error( $e->getMessage() );
			}
		}

		/**
		 * Handle unverify user action.
		 *
		 * Removes verification status from user and sends success response.
		 *
		 * @since 1.0.0
		 * @param int $user_id User ID to unverify.
		 * @return void
		 */
		private function handle_unverify_user_action( $user_id ) {
			delete_user_meta( $user_id, self::VERIFICATION_META_KEY );
			CEV_Ajax_Response::send_success(
				array(
					'userId'     => $user_id,
					'isVerified' => false,
				),
				__( 'User unverified successfully.', 'customer-email-verification' ),
				'user_unverified'
			);
		}

		/**
		 * Handle verify user action.
		 *
		 * Marks user as verified, triggers delayed email if enabled, and sends success response.
		 *
		 * @since 1.0.0
		 * @param int $user_id User ID to verify.
		 * @return void
		 */
		private function handle_verify_user_action( $user_id ) {
			update_user_meta( $user_id, self::VERIFICATION_META_KEY, 'true' );
			$this->trigger_delay_new_account_email( $user_id );
			CEV_Ajax_Response::send_success(
				array(
					'userId'     => $user_id,
					'isVerified' => true,
				),
				__( 'User verified successfully.', 'customer-email-verification' ),
				'user_verified'
			);
		}

		/**
		 * Trigger the delayed new account email for a customer after verification.
		 *
		 * Sends the WooCommerce "New Account" email if the delayed email option
		 * is enabled and the user has just been verified.
		 *
		 * @since 1.0.0
		 * @param int $user_id The ID of the user to send the email to.
		 * @return void
		 */
		public function trigger_delay_new_account_email( $user_id ) {
			// Check if delayed email option is enabled.
			if ( '1' !== get_option( 'delay_new_account_email_customer' ) ) {
				return;
			}

			// Ensure WooCommerce is available.
			if ( ! function_exists( 'WC' ) ) {
				return;
			}

			$emails = WC()->mailer()->emails;

			if ( ! isset( $emails['WC_Email_Customer_New_Account'] ) ) {
				return;
			}

			// Get user data.
			$new_customer_data = get_userdata( $user_id );
			if ( ! $new_customer_data ) {
				return;
			}

			// Get user password if available.
			$user_pass = isset( $new_customer_data->user_pass ) ? $new_customer_data->user_pass : '';

			// Trigger the "New Account" email.
			$email = $emails['WC_Email_Customer_New_Account'];
			$email->trigger( $user_id, $user_pass, false );
		}

		/**
		 * Display success notice for manual user verification.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function manual_cev_verify_email_success_admin() {
			$this->render_admin_notice( __( 'User verified successfully.', 'customer-email-verification' ) );
		}

		/**
		 * Display notice for manual user unverification.
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function manual_cev_verify_email_unverify_admin() {
			$this->render_admin_notice( __( 'User unverified.', 'customer-email-verification' ) );
		}

		/**
		 * Render a generic admin notice.
		 *
		 * @since 1.0.0
		 * @param string $message The message to display.
		 * @return void
		 */
		public function render_admin_notice( $message ) {
			$template_path = $this->get_view_path( 'user/admin-notice' );
			if ( file_exists( $template_path ) ) {
				include $template_path;
			}
		}

		/**
		 * Check if a user is an administrator.
		 *
		 * Delegates to the admin class method to check if a user has administrator role.
		 *
		 * @since 1.0.0
		 * @param int $user_id User ID.
		 * @return bool True if the user is an administrator, false otherwise.
		 */
		private function is_admin_user( $user_id ) {
			if ( $this->admin ) {
				return $this->admin->is_admin_user( $user_id );
			}
			return false;
		}

	}
}
