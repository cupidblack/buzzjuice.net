<?php
/**
 * Addon AI Admin Module Class
 *
 * Extends the myCRED_Module class to register AI abilities and the administrative chat page.
 *
 * @package myCred
 * @subpackage AI
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'myCRED_AI_Admin' ) ) :

	class myCRED_AI_Admin extends myCRED_Module {

		/**
		 * Singleton Instance
		 *
		 * @var myCRED_AI_Admin|null
		 */
		protected static $_instance = null;

		/**
		 * Setup Instance Singleton
		 *
		 * @since 1.0
		 * @return myCRED_AI_Admin
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Construct
		 *
		 * Sets up our custom module configuration matching myCRED_Module patterns.
		 */
		public function __construct() {
			parent::__construct( 'myCRED_AI_Admin', array(
				'module_name' => 'ai',
				'defaults'    => array(),
				'register'    => false,
				'screen_id'   => 'mycred-ai-assistant',
				'labels'      => array(
					'menu'       => 'AI Assistant',
					'page_title' => 'AI Assistant'
				),
				'accordion'   => false,
				'add_to_core' => false,
				'cap'         => 'plugin', // Enforce point admin/editor capability requirements
				'menu_pos'    => 5,
				'main_menu'   => true
			) );

			$this->load();
		}

		/**
		 * Hooks Initialization (menu is registered in myCred core).
		 */
		public function load() {

			if ( ! empty( $this->screen_id ) ) {
				add_action( 'admin_init', array( $this, 'set_entries_per_page' ) );
			}

			if ( $this->register === true && ! empty( $this->option_id ) ) {
				add_action( 'mycred_admin_init', array( $this, 'register_settings' ), $this->menu_pos );
			}

			if ( $this->add_to_core === true ) {
				add_action( 'mycred_after_core_prefs', array( $this, 'after_general_settings' ) );
				add_filter( 'mycred_save_core_prefs', array( $this, 'sanitize_extra_settings' ), 90, 3 );
			}

			add_action( 'mycred_pre_init', array( $this, 'module_pre_init' ) );
			add_action( 'mycred_init', array( $this, 'module_init' ) );
			add_action( 'mycred_admin_init', array( $this, 'module_admin_init' ), $this->menu_pos + 1 );
			add_action( 'mycred_widgets_init', array( $this, 'module_widgets_init' ) );
			add_action( 'mycred_admin_enqueue', array( $this, 'scripts_and_styles' ), $this->menu_pos );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_abilities_api_categories_init', array( $this, 'register_abilities_category' ) );
			add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
			add_action( 'wp_ajax_mycred_ai_chat', array( $this, 'handle_chat_ajax' ) );
		}

		/**
		 * Admin menu is registered by myCred core (mycred-ai-assistant-menu.php).
		 */
		public function add_menu() {
			return;
		}

		/**
		 * Prevent errors from other parts of myCred settings pages
		 */
		public function settings_section_fallback() {
			// Fallback to avoid notices or warnings
		}

		/**
		 * Initials for the current admin user (chat avatar).
		 *
		 * @return string
		 */
		public function get_current_user_initials() {
			$user = wp_get_current_user();
			if ( ! $user->exists() ) {
				return '?';
			}

			$first = trim( (string) get_user_meta( $user->ID, 'first_name', true ) );
			$last  = trim( (string) get_user_meta( $user->ID, 'last_name', true ) );

			if ( $first !== '' && $last !== '' ) {
				return strtoupper( mb_substr( $first, 0, 1 ) . mb_substr( $last, 0, 1 ) );
			}

			$parts = preg_split( '/\s+/', trim( $user->display_name ) );
			if ( is_array( $parts ) && count( $parts ) >= 2 ) {
				return strtoupper( mb_substr( $parts[0], 0, 1 ) . mb_substr( end( $parts ), 0, 1 ) );
			}

			$fallback = $user->display_name ? $user->display_name : $user->user_login;
			return strtoupper( mb_substr( $fallback, 0, 2 ) );
		}

		/**
		 * URL for the AI chat avatar image.
		 *
		 * @return string
		 */
		public function get_ai_avatar_url() {
			if ( defined( 'MYCRED_AI_PLUGIN_FILE' ) ) {
				$local_svg = MYCRED_AI_ROOT_DIR . 'assets/images/ai-avatar.svg';
				if ( file_exists( $local_svg ) ) {
					return plugins_url( 'assets/images/ai-avatar.svg', MYCRED_AI_PLUGIN_FILE );
				}

				$local_png = MYCRED_AI_ROOT_DIR . 'assets/images/ai-avatar.png';
				if ( file_exists( $local_png ) ) {
					return plugins_url( 'assets/images/ai-avatar.png', MYCRED_AI_PLUGIN_FILE );
				}
			}

			if ( defined( 'MYCRED_THIS' ) && file_exists( MYCRED_THIS . 'assets/images/mycred-logo.svg' ) ) {
				return plugins_url( 'assets/images/mycred-logo.svg', MYCRED_THIS );
			}

			return MYCRED_AI_ASSETS_URL . 'images/ai-avatar.svg';
		}

		/**
		 * Check if we are on the AI Assistant admin page
		 *
		 * @param string $hook_suffix The current screen hook suffix.
		 * @return bool
		 */
		public function is_ai_assistant_admin_screen( $hook_suffix ) {
			return strpos( $hook_suffix, 'mycred-ai-assistant' ) !== false;
		}

		/**
		 * Enqueue Styles and Scripts on our custom admin page
		 *
		 * @param string $hook_suffix Current hook suffix.
		 */
		public function enqueue_assets( $hook_suffix ) {
			if ( ! $this->is_ai_assistant_admin_screen( $hook_suffix ) ) {
				return;
			}

			// Enqueue Outfit and Inter fonts from Google Fonts for rich visual design
			wp_enqueue_style( 'mycred-ai-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap', array(), null );

			// Enqueue our beautiful glassmorphic CSS styling
			wp_enqueue_style( 'mycred-ai-admin-css', plugins_url( '../assets/css/mycred-ai-admin.css', __FILE__ ), array(), MYCRED_AI_VERSION );

			// Enqueue Vanilla JS scripts
			wp_enqueue_script( 'mycred-ai-admin-js', plugins_url( '../assets/js/mycred-ai-admin.js', __FILE__ ), array( 'jquery' ), MYCRED_AI_VERSION, true );

			$current_user = wp_get_current_user();

			wp_localize_script( 'mycred-ai-admin-js', 'mycredAi', array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'mycred_ai_chat_nonce' ),
				'avatar_url'     => $this->get_ai_avatar_url(),
				'user_initials'  => $this->get_current_user_initials(),
				'user_name'      => $current_user->exists() ? $current_user->display_name : '',
				'strings'        => array(
					'error'   => __( 'Something went wrong. Please try again.', 'mycred' ),
					'sending' => __( 'Thinking...', 'mycred' ),
				),
				'ai_settings_url' => $this->get_ai_connectors_settings_url(),
			) );
		}

		/**
		 * Registers the custom myCred AI abilities category
		 */
		public function register_abilities_category() {
			wp_register_ability_category( 'mycred', array(
				'label'       => __( 'myCred Points Management', 'mycred' ),
				'description' => __( 'Abilities to retrieve point balances, site summaries, award or deduct points, create point types, ranks, and badges.', 'mycred' )
			) );
		}

		/**
		 * Registers custom abilities with WordPress 7.0 AI Core
		 */
		public function register_abilities() {
			// Ability 1: get-user-balance
			wp_register_ability( 'mycred/get-user-balance', array(
				'label'               => __( 'Get User Balance', 'mycred' ),
				'description'         => __( 'Retrieves the current points balance of a user by user ID, email, or username.', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_get_user_balance' ),
				'permission_callback' => array( $this, 'permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'user' => array(
							'type'        => 'string',
							'description' => 'The user ID, email, or username to fetch balance for.',
						),
						'point_type' => array(
							'type'        => 'string',
							'description' => 'The point type key. Optional, defaults to default type.',
						),
					),
					'required'   => array( 'user' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'user_id'           => array( 'type' => 'integer' ),
						'username'          => array( 'type' => 'string' ),
						'balance'           => array( 'type' => 'number' ),
						'formatted_balance' => array( 'type' => 'string' ),
						'point_type'        => array( 'type' => 'string' ),
					),
					'required'   => array( 'user_id', 'username', 'balance', 'formatted_balance' ),
				),
			) );

			// Ability 2: get-site-points-summary
			wp_register_ability( 'mycred/get-site-points-summary', array(
				'label'               => __( 'Get Site Points Summary', 'mycred' ),
				'description'         => __( 'Retrieves total points in circulation, total log entries, and active members count.', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_get_site_points_summary' ),
				'permission_callback' => array( $this, 'permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'point_type' => array(
							'type'        => 'string',
							'description' => 'The point type key. Optional, defaults to default type.',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'point_type'                  => array( 'type' => 'string' ),
						'point_type_label'            => array( 'type' => 'string' ),
						'total_circulation'           => array( 'type' => 'number' ),
						'formatted_total_circulation' => array( 'type' => 'string' ),
						'total_log_entries'           => array( 'type' => 'integer' ),
						'active_members_count'        => array( 'type' => 'integer' ),
					),
					'required'   => array( 'point_type', 'total_circulation', 'formatted_total_circulation', 'total_log_entries', 'active_members_count' ),
				),
			) );

			// Ability 3: award-points
			wp_register_ability( 'mycred/award-points', array(
				'label'               => __( 'Award Points', 'mycred' ),
				'description'         => __( 'Awards points to a specified user with a given reason.', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_award_points' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'user' => array(
							'type'        => 'string',
							'description' => 'The user ID, email, or username to award points to.',
						),
						'amount' => array(
							'type'        => 'number',
							'description' => 'The number of points to award (must be a positive number).',
						),
						'reason' => array(
							'type'        => 'string',
							'description' => 'The reason or description for awarding points.',
						),
						'point_type' => array(
							'type'        => 'string',
							'description' => 'The point type key. Optional, defaults to default type.',
						),
					),
					'required'   => array( 'user', 'amount', 'reason' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'               => array( 'type' => 'boolean' ),
						'message'               => array( 'type' => 'string' ),
						'new_balance'           => array( 'type' => 'number' ),
						'formatted_new_balance' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'message', 'new_balance', 'formatted_new_balance' ),
				),
			) );

			// Ability 4: deduct-points
			wp_register_ability( 'mycred/deduct-points', array(
				'label'               => __( 'Deduct Points', 'mycred' ),
				'description'         => __( 'Deducts points from a specified user with a given reason.', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_deduct_points' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'user' => array(
							'type'        => 'string',
							'description' => 'The user ID, email, or username to deduct points from.',
						),
						'amount' => array(
							'type'        => 'number',
							'description' => 'The number of points to deduct (must be a positive number).',
						),
						'reason' => array(
							'type'        => 'string',
							'description' => 'The reason or description for deducting points.',
						),
						'point_type' => array(
							'type'        => 'string',
							'description' => 'The point type key. Optional, defaults to default type.',
						),
					),
					'required'   => array( 'user', 'amount', 'reason' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'               => array( 'type' => 'boolean' ),
						'message'               => array( 'type' => 'string' ),
						'new_balance'           => array( 'type' => 'number' ),
						'formatted_new_balance' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'message', 'new_balance', 'formatted_new_balance' ),
				),
			) );

			// Ability 5: suggest-hooks
			wp_register_ability( 'mycred/suggest-hooks', array(
				'label'               => __( 'Suggest Hooks', 'mycred' ),
				'description'         => __( 'Suggests a list of appropriate myCred hooks based on the site type.', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_suggest_hooks' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'site_type' => array(
							'type'        => 'string',
							'description' => 'The type of the site (e.g., eCommerce, LMS, BuddyBoss, Community, Blog).',
						),
						'point_type' => array(
							'type'        => 'string',
							'description' => 'The point type key. Optional, defaults to default type.',
						),
					),
					'required'   => array( 'site_type' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'suggested_hooks' => array(
							'type'        => 'array',
							'description' => 'List of suggested hooks with their descriptions.',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'hook_id'     => array( 'type' => 'string', 'description' => 'The internal hook ID (e.g., logging_in, publishing_content, comments)' ),
									'description' => array( 'type' => 'string', 'description' => 'Explanation of what this hook does and why it is recommended.' ),
								)
							)
						),
					),
					'required'   => array( 'suggested_hooks' ),
				),
			) );

			// Ability 6: apply-hooks
			wp_register_ability( 'mycred/apply-hooks', array(
				'label'               => __( 'Apply Hooks', 'mycred' ),
				'description'         => __( 'Overwrites current active hooks and applies the provided hook configuration with their point amounts.', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_apply_hooks' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'hooks' => array(
							'type'        => 'array',
							'description' => 'List of hooks to apply.',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'hook_id' => array( 'type' => 'string', 'description' => 'The internal hook ID (e.g., logging_in)' ),
									'creds'   => array( 'type' => 'number', 'description' => 'The amount of points to award/deduct for this hook.' ),
									'log'     => array( 'type' => 'string', 'description' => 'The log template message for this hook (e.g., %plural% for logging in).' ),
								),
								'required'   => array( 'hook_id', 'creds' )
							)
						),
						'point_type' => array(
							'type'        => 'string',
							'description' => 'The point type key. Optional, defaults to default type.',
						),
					),
					'required'   => array( 'hooks' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'message' ),
				),
			) );

			// Ability 7: create-point-type
			wp_register_ability( 'mycred/create-point-type', array(
				'label'               => __( 'Create Point Type', 'mycred' ),
				'description'         => __( 'Registers a new myCred point type with a unique meta key and display names.', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_create_point_type' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'key' => array(
							'type'        => 'string',
							'description' => 'The meta key for the new point type. Lowercase letters and underscores only; spaces and hyphens are converted to underscores.',
						),
						'plural' => array(
							'type'        => 'string',
							'description' => 'The plural display name for the point type (e.g., Gold Coins).',
						),
						'singular' => array(
							'type'        => 'string',
							'description' => 'The singular display name. Optional; defaults to the plural name if omitted.',
						),
					),
					'required'   => array( 'key', 'plural' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'    => array( 'type' => 'boolean' ),
						'message'    => array( 'type' => 'string' ),
						'point_type' => array( 'type' => 'string' ),
						'singular'   => array( 'type' => 'string' ),
						'plural'     => array( 'type' => 'string' ),
					),
					'required'   => array( 'success', 'message', 'point_type', 'singular', 'plural' ),
				),
			) );

			if ( $this->is_ranks_addon_active() ) {
				$this->register_ranks_abilities();
			}

			if ( $this->is_badges_addon_active() ) {
				$this->register_badges_abilities();
			}
		}

		/**
		 * Register ranks-related abilities when the built-in Ranks addon is active.
		 */
		protected function register_ranks_abilities() {
			wp_register_ability( 'mycred/suggest-ranks', array(
				'label'               => __( 'Suggest Ranks', 'mycred' ),
				'description'         => __( 'Suggests a progression of rank names and point thresholds based on the site type (e.g. Community, LMS, eCommerce).', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_suggest_ranks' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'site_type' => array(
							'type'        => 'string',
							'description' => 'The type of site (e.g., Community, BuddyBoss, LMS, eCommerce, Blog).',
						),
						'point_type' => array(
							'type'        => 'string',
							'description' => 'The point type key ranks belong to. Optional, defaults to default type.',
						),
					),
					'required'   => array( 'site_type' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'suggested_ranks' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'title'   => array( 'type' => 'string' ),
									'minimum' => array( 'type' => 'number' ),
									'maximum' => array( 'type' => 'number' ),
									'description' => array( 'type' => 'string' ),
								),
							),
						),
						'point_type' => array( 'type' => 'string' ),
					),
					'required'   => array( 'suggested_ranks', 'point_type' ),
				),
			) );

			wp_register_ability( 'mycred/create-ranks', array(
				'label'               => __( 'Create Ranks', 'mycred' ),
				'description'         => __( 'Creates published myCred ranks with title and min/max point thresholds.', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_create_ranks' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'ranks' => array(
							'type'        => 'array',
							'description' => 'List of ranks to create.',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'title'   => array( 'type' => 'string', 'description' => 'Rank display name.' ),
									'minimum' => array( 'type' => 'number', 'description' => 'Minimum points required (inclusive).' ),
									'maximum' => array( 'type' => 'number', 'description' => 'Maximum points for this rank (inclusive).' ),
								),
								'required'   => array( 'title', 'minimum', 'maximum' ),
							),
						),
						'point_type' => array(
							'type'        => 'string',
							'description' => 'The point type key. Optional, defaults to default type.',
						),
					),
					'required'   => array( 'ranks' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'       => array( 'type' => 'boolean' ),
						'message'       => array( 'type' => 'string' ),
						'created_ranks' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'rank_id' => array( 'type' => 'integer' ),
									'title'   => array( 'type' => 'string' ),
									'minimum' => array( 'type' => 'number' ),
									'maximum' => array( 'type' => 'number' ),
								),
							),
						),
					),
					'required'   => array( 'success', 'message', 'created_ranks' ),
				),
			) );
		}

		/**
		 * Register badges-related abilities when the built-in Badges addon is active.
		 */
		protected function register_badges_abilities() {
			wp_register_ability( 'mycred/suggest-badges', array(
				'label'               => __( 'Suggest Badges', 'mycred' ),
				'description'         => __( 'Suggests badge names and earning criteria based on the site type (e.g. Community, LMS).', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_suggest_badges' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'site_type' => array(
							'type'        => 'string',
							'description' => 'The type of site (e.g., Community, BuddyBoss, LMS, eCommerce, Blog).',
						),
						'point_type' => array(
							'type'        => 'string',
							'description' => 'The point type key for automatic badge requirements. Optional, defaults to default type.',
						),
					),
					'required'   => array( 'site_type' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'suggested_badges' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'title'       => array( 'type' => 'string' ),
									'description' => array( 'type' => 'string' ),
									'manual'      => array( 'type' => 'boolean' ),
									'reference'   => array( 'type' => 'string' ),
									'amount'      => array( 'type' => 'string' ),
									'by'          => array( 'type' => 'string' ),
								),
							),
						),
						'point_type' => array( 'type' => 'string' ),
					),
					'required'   => array( 'suggested_badges', 'point_type' ),
				),
			) );

			wp_register_ability( 'mycred/create-badges', array(
				'label'               => __( 'Create Badges', 'mycred' ),
				'description'         => __( 'Creates published myCred badges, optionally with automatic earning requirements.', 'mycred' ),
				'category'            => 'mycred',
				'execute_callback'    => array( $this, 'execute_create_badges' ),
				'permission_callback' => array( $this, 'admin_permission_callback' ),
				'input_schema'        => array(
					'type'       => 'object',
					'properties' => array(
						'badges' => array(
							'type'        => 'array',
							'description' => 'List of badges to create.',
							'items'       => array(
								'type'       => 'object',
								'properties' => array(
									'title'       => array( 'type' => 'string', 'description' => 'Badge name.' ),
									'description' => array( 'type' => 'string', 'description' => 'Optional badge description shown in admin.' ),
									'manual'      => array( 'type' => 'boolean', 'description' => 'If true, badge is manually awarded by admins only.' ),
									'reference'   => array( 'type' => 'string', 'description' => 'Hook reference for automatic badges (e.g. logging_in, registration, comments).' ),
									'amount'      => array( 'type' => 'string', 'description' => 'Required count/amount for the reference (e.g. "10").' ),
									'by'          => array( 'type' => 'string', 'description' => 'Requirement comparison: count, sum, etc. Defaults to count.' ),
								),
								'required'   => array( 'title' ),
							),
						),
						'point_type' => array(
							'type'        => 'string',
							'description' => 'Point type for automatic requirements. Optional, defaults to default type.',
						),
					),
					'required'   => array( 'badges' ),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'        => array( 'type' => 'boolean' ),
						'message'        => array( 'type' => 'string' ),
						'created_badges' => array(
							'type'  => 'array',
							'items' => array(
								'type'       => 'object',
								'properties' => array(
									'badge_id' => array( 'type' => 'integer' ),
									'title'    => array( 'type' => 'string' ),
									'manual'   => array( 'type' => 'boolean' ),
								),
							),
						),
					),
					'required'   => array( 'success', 'message', 'created_badges' ),
				),
			) );
		}

		/**
		 * Whether a built-in myCred addon slug is enabled.
		 *
		 * @param string $slug Addon slug (e.g. ranks, badges).
		 * @return bool
		 */
		public function is_addon_active( $slug ) {
			$addons_prefs = get_option( 'mycred_pref_addons', array() );
			$active       = ! empty( $addons_prefs['active'] ) ? $addons_prefs['active'] : array();
			return in_array( $slug, $active, true );
		}

		/**
		 * Whether the built-in Ranks addon is enabled and loaded.
		 *
		 * @return bool
		 */
		public function is_ranks_addon_active() {
			return class_exists( 'myCRED_Ranks_Module' ) && $this->is_addon_active( 'ranks' );
		}

		/**
		 * Whether the built-in Badges addon is enabled and loaded.
		 *
		 * @return bool
		 */
		public function is_badges_addon_active() {
			return class_exists( 'myCRED_Badge_Module' ) && $this->is_addon_active( 'badges' );
		}

		/**
		 * Ability IDs exposed to the AI chat for the current site.
		 *
		 * @return string[]
		 */
		public function get_chat_abilities() {
			$abilities = array(
				'mycred/get-user-balance',
				'mycred/get-site-points-summary',
				'mycred/award-points',
				'mycred/deduct-points',
				'mycred/suggest-hooks',
				'mycred/apply-hooks',
				'mycred/create-point-type',
			);

			if ( $this->is_ranks_addon_active() ) {
				$abilities[] = 'mycred/suggest-ranks';
				$abilities[] = 'mycred/create-ranks';
			}

			if ( $this->is_badges_addon_active() ) {
				$abilities[] = 'mycred/suggest-badges';
				$abilities[] = 'mycred/create-badges';
			}

			/**
			 * Filter abilities exposed via myCred MCP Bridge (and other MCP integrations).
			 *
			 * @param string[] $abilities Ability slugs.
			 */
			return apply_filters( 'mycred_mcp_exposed_abilities', $abilities );
		}

		/**
		 * Read ability permission check
		 */
		public function permission_callback( $input ) {
			$cap = $this->core->get_point_editor_capability();
			return current_user_can( $cap ) || current_user_can( 'manage_options' );
		}

		/**
		 * Write/Mutation ability permission check (Strict Point Administrator)
		 */
		public function admin_permission_callback( $input ) {
			$cap = $this->core->get_point_admin_capability();
			return current_user_can( $cap ) || current_user_can( 'manage_options' );
		}

		/**
		 * Ability Callback: Get User Balance
		 */
		public function execute_get_user_balance( $input ) {
			$user_identifier = isset( $input['user'] ) ? sanitize_text_field( $input['user'] ) : '';
			$point_type      = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;

			$user_id = $this->resolve_user_id( $user_identifier );
			if ( ! $user_id ) {
				return new WP_Error( 'user_not_found', __( 'Specified user could not be found.', 'mycred' ) );
			}

			$mycred  = mycred( $point_type );
			$balance = mycred_get_users_balance( $user_id, $point_type );

			if ( $balance === false ) {
				return new WP_Error( 'excluded_user', __( 'This user is excluded from myCred or cannot be queried.', 'mycred' ) );
			}

			$user_obj = get_userdata( $user_id );
			return array(
				'user_id'           => $user_id,
				'username'          => $user_obj->user_login,
				'balance'           => (float) $balance,
				'formatted_balance' => $mycred->format_creds( $balance ),
				'point_type'        => $point_type,
			);
		}

		/**
		 * Ability Callback: Get Site Points Summary
		 */
		public function execute_get_site_points_summary( $input ) {
			global $wpdb;
			$point_type = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;
			$mycred     = mycred( $point_type );

			// Total circulation query
			$total_circulation = $wpdb->get_var( $wpdb->prepare(
				"SELECT SUM( CAST( meta_value AS DECIMAL( 18, 4 ) ) ) FROM {$wpdb->usermeta} WHERE meta_key = %s",
				$point_type
			) );
			$total_circulation = $total_circulation ? (float) $total_circulation : 0.0;

			// Total log entries
			$log_table         = $mycred->log_table;
			$total_log_entries = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$log_table} WHERE ctype = %s",
				$point_type
			) );
			$total_log_entries = $total_log_entries ? (int) $total_log_entries : 0;

			// Active members count (users who have at least one log entry)
			$active_members_count = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(DISTINCT user_id) FROM {$log_table} WHERE ctype = %s",
				$point_type
			) );
			$active_members_count = $active_members_count ? (int) $active_members_count : 0;

			// If no log entries, fall back to users who have user meta values
			if ( $active_members_count === 0 ) {
				$active_members_count = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND CAST(meta_value AS DECIMAL(18,4)) != 0",
					$point_type
				) );
				$active_members_count = $active_members_count ? (int) $active_members_count : 0;
			}

			// Point type label
			$point_type_label = isset( $this->point_types[ $point_type ] ) ? $this->point_types[ $point_type ] : $mycred->core['name']['singular'];

			return array(
				'point_type'                  => $point_type,
				'point_type_label'            => $point_type_label,
				'total_circulation'           => $total_circulation,
				'formatted_total_circulation' => $mycred->format_creds( $total_circulation ),
				'total_log_entries'           => $total_log_entries,
				'active_members_count'        => $active_members_count,
			);
		}

		/**
		 * Ability Callback: Award Points
		 */
		public function execute_award_points( $input ) {
			$user_identifier = isset( $input['user'] ) ? sanitize_text_field( $input['user'] ) : '';
			$amount          = isset( $input['amount'] ) ? (float) $input['amount'] : 0.0;
			$reason          = isset( $input['reason'] ) ? sanitize_text_field( $input['reason'] ) : '';
			$point_type      = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;

			if ( $amount <= 0 ) {
				return new WP_Error( 'invalid_amount', __( 'Award amount must be greater than zero.', 'mycred' ) );
			}

			$user_id = $this->resolve_user_id( $user_identifier );
			if ( ! $user_id ) {
				return new WP_Error( 'user_not_found', __( 'Specified user could not be found.', 'mycred' ) );
			}

			$mycred = mycred( $point_type );
			if ( $mycred->exclude_user( $user_id ) ) {
				return new WP_Error( 'excluded_user', __( 'This user is excluded from myCred and cannot receive points.', 'mycred' ) );
			}

			// Award points using mycred_add
			$success = mycred_add(
				'ai_assistant',
				$user_id,
				$amount,
				$reason,
				get_current_user_id(),
				'',
				$point_type
			);

			if ( ! $success ) {
				return new WP_Error( 'award_failed', __( 'Could not award points to the user.', 'mycred' ) );
			}

			$new_balance = mycred_get_users_balance( $user_id, $point_type );
			$user_obj    = get_userdata( $user_id );

			return array(
				'success'               => true,
				'message'               => sprintf( __( 'Successfully awarded %1$s to %2$s.', 'mycred' ), $mycred->format_creds( $amount ), $user_obj->user_login ),
				'new_balance'           => (float) $new_balance,
				'formatted_new_balance' => $mycred->format_creds( $new_balance ),
			);
		}

		/**
		 * Ability Callback: Deduct Points
		 */
		public function execute_deduct_points( $input ) {
			$user_identifier = isset( $input['user'] ) ? sanitize_text_field( $input['user'] ) : '';
			$amount          = isset( $input['amount'] ) ? (float) $input['amount'] : 0.0;
			$reason          = isset( $input['reason'] ) ? sanitize_text_field( $input['reason'] ) : '';
			$point_type      = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;

			if ( $amount <= 0 ) {
				return new WP_Error( 'invalid_amount', __( 'Deduct amount must be greater than zero.', 'mycred' ) );
			}

			$user_id = $this->resolve_user_id( $user_identifier );
			if ( ! $user_id ) {
				return new WP_Error( 'user_not_found', __( 'Specified user could not be found.', 'mycred' ) );
			}

			$mycred = mycred( $point_type );
			if ( $mycred->exclude_user( $user_id ) ) {
				return new WP_Error( 'excluded_user', __( 'This user is excluded from myCred and cannot have points deducted.', 'mycred' ) );
			}

			// Deduct points using mycred_subtract
			$success = mycred_subtract(
				'ai_assistant',
				$user_id,
				$amount,
				$reason,
				get_current_user_id(),
				'',
				$point_type
			);

			if ( ! $success ) {
				return new WP_Error( 'deduct_failed', __( 'Could not deduct points from the user.', 'mycred' ) );
			}

			$new_balance = mycred_get_users_balance( $user_id, $point_type );
			$user_obj    = get_userdata( $user_id );

			return array(
				'success'               => true,
				'message'               => sprintf( __( 'Successfully deducted %1$s from %2$s.', 'mycred' ), $mycred->format_creds( $amount ), $user_obj->user_login ),
				'new_balance'           => (float) $new_balance,
				'formatted_new_balance' => $mycred->format_creds( $new_balance ),
			);
		}

		/**
		 * Ability Callback: Suggest Hooks
		 */
		public function execute_suggest_hooks( $input ) {
			$site_type  = isset( $input['site_type'] ) ? sanitize_text_field( $input['site_type'] ) : 'Blog';
			$point_type = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;
			
			$option_key     = $point_type === MYCRED_DEFAULT_TYPE_KEY ? 'mycred_pref_hooks' : 'mycred_pref_hooks_' . $point_type;
			$hooks_settings = get_option( $option_key, array() );
			
			$installed_hooks = isset( $hooks_settings['installed'] ) ? array_keys( $hooks_settings['installed'] ) : array();

			$all_possible_hooks = array(
				'logging_in'           => 'Awards points when a user logs in to the site.',
				'publishing_content'   => 'Awards points when a user publishes a post or page.',
				'comments'             => 'Awards points when a user leaves a comment.',
				'registration'         => 'Awards points when a new user registers on the site.',
				'site_visit'           => 'Awards points when a user visits the site daily.',
				'woocommerce_purchase' => 'Awards points when a user purchases a product.',
				'woocommerce_review'   => 'Awards points when a user leaves a product review.',
				'buddypress_activity'  => 'Awards points for BuddyPress activity updates.',
				'buddypress_avatar'    => 'Awards points for uploading a profile avatar.'
			);

			$available_hooks = array();
			
			foreach ( $all_possible_hooks as $hook_id => $desc ) {
				// Only suggest if the hook is actually installed (or if installed list is missing, assume true to be safe)
				if ( empty( $installed_hooks ) || in_array( $hook_id, $installed_hooks ) ) {
					$available_hooks[] = array( 'hook_id' => $hook_id, 'description' => $desc );
				}
			}

			return array(
				'suggested_hooks' => $available_hooks,
			);
		}

		/**
		 * Ability Callback: Apply Hooks (Overwrite)
		 */
		public function execute_apply_hooks( $input ) {
			$hooks      = isset( $input['hooks'] ) && is_array( $input['hooks'] ) ? $input['hooks'] : array();
			$point_type = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;

			if ( empty( $hooks ) ) {
				return new WP_Error( 'no_hooks', __( 'No hooks provided to apply.', 'mycred' ) );
			}

			$option_key     = $point_type === MYCRED_DEFAULT_TYPE_KEY ? 'mycred_pref_hooks' : 'mycred_pref_hooks_' . $point_type;
			$hooks_settings = get_option( $option_key, array() );
			
			if ( ! isset( $hooks_settings['active'] ) ) {
				$hooks_settings['active'] = array();
			}
			if ( ! isset( $hooks_settings['hook_prefs'] ) ) {
				$hooks_settings['hook_prefs'] = array();
			}

			$installed_hooks = isset( $hooks_settings['installed'] ) ? array_keys( $hooks_settings['installed'] ) : array();

			// Overwrite the active hooks array with the new list
			$active_hooks = array();

			foreach ( $hooks as $hook ) {
				if ( empty( $hook['hook_id'] ) ) {
					continue;
				}
				$hook_id = sanitize_text_field( $hook['hook_id'] );
				
				// Ensure the hook is actually available on the site
				if ( ! empty( $installed_hooks ) && ! in_array( $hook_id, $installed_hooks ) ) {
					continue;
				}
				
				$active_hooks[] = $hook_id;

				$creds = isset( $hook['creds'] ) ? (float) $hook['creds'] : 1;
				$log   = isset( $hook['log'] ) && ! empty( $hook['log'] ) ? sanitize_text_field( $hook['log'] ) : '%plural% for ' . str_replace( '_', ' ', $hook_id );

				// Map complex hook structures to avoid array offset warnings in hook classes
				if ( $hook_id === 'publishing_content' ) {
					$hooks_settings['hook_prefs'][ $hook_id ] = array(
						'post' => array( 'creds' => $creds, 'log' => $log, 'limit' => '0/x' ),
						'page' => array( 'creds' => $creds, 'log' => $log, 'limit' => '0/x' )
					);
				} elseif ( $hook_id === 'comments' ) {
					$hooks_settings['hook_prefs'][ $hook_id ] = array(
						'approved'   => array( 'creds' => $creds, 'log' => $log, 'limit' => '0/x', 'author' => 0 ),
						'spam'       => array( 'creds' => 0, 'log' => '%plural% deduction for Comment marked as spam', 'author' => 0 ),
						'trash'      => array( 'creds' => -1, 'log' => '%plural% deduction for deleted / unapproved comment', 'author' => 0 ),
						'self_reply' => 0
					);
				} elseif ( $hook_id === 'registration' ) {
					$hooks_settings['hook_prefs'][ $hook_id ] = array(
						'creds' => $creds,
						'log'   => $log
					);
				} elseif ( $hook_id === 'site_visit' ) {
					$hooks_settings['hook_prefs'][ $hook_id ] = array(
						'creds' => $creds,
						'log'   => $log,
						'limit' => '1/d'
					);
				} else {
					// Default simple mapping
					$hooks_settings['hook_prefs'][ $hook_id ] = array(
						'creds' => $creds,
						'log'   => $log,
						'limit' => '0/x'
					);
				}
			}

			// In myCred, active hooks are stored directly in mycred_pref_hooks['active']
			$hooks_settings['active'] = $active_hooks;

			// Save options
			update_option( $option_key, $hooks_settings );

			return array(
				'success' => true,
				'message' => sprintf( __( 'Successfully applied %d available hooks. Existing hooks were overwritten.', 'mycred' ), count( $active_hooks ) ),
			);
		}

		/**
		 * Ability Callback: Create Point Type
		 */
		public function execute_create_point_type( $input ) {
			$key      = isset( $input['key'] ) ? sanitize_text_field( $input['key'] ) : '';
			$plural   = isset( $input['plural'] ) ? sanitize_text_field( $input['plural'] ) : '';
			$singular = isset( $input['singular'] ) ? sanitize_text_field( $input['singular'] ) : '';

			if ( empty( $key ) || empty( $plural ) ) {
				return new WP_Error( 'missing_fields', __( 'Both key and plural name are required to create a point type.', 'mycred' ) );
			}

			$key = str_replace( array( ' ', '-' ), '_', $key );
			$key = sanitize_key( $key );

			if ( empty( $key ) ) {
				return new WP_Error( 'invalid_key', __( 'The point type key is invalid after sanitization.', 'mycred' ) );
			}

			if ( $key === MYCRED_DEFAULT_TYPE_KEY ) {
				return new WP_Error( 'invalid_key', __( 'The default point type key cannot be used for a new point type.', 'mycred' ) );
			}

			if ( function_exists( 'mycred_point_type_exists' ) && mycred_point_type_exists( $key ) ) {
				return new WP_Error( 'point_type_exists', __( 'A point type with this key already exists.', 'mycred' ) );
			}

			$types = mycred_get_option( 'mycred_types', array( MYCRED_DEFAULT_TYPE_KEY => mycred_label() ) );
			if ( array_key_exists( $key, $types ) ) {
				return new WP_Error( 'point_type_exists', __( 'A point type with this key already exists.', 'mycred' ) );
			}

			if ( empty( $singular ) ) {
				$singular = $plural;
			}

			$result = $this->create_point_type( $key, $plural, $singular );
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			return array(
				'success'    => true,
				'message'    => sprintf(
					__( 'Successfully created point type "%1$s" (key: %2$s). Reload the admin page to see the new type in menus and settings.', 'mycred' ),
					$plural,
					$key
				),
				'point_type' => $key,
				'singular'   => $singular,
				'plural'     => $plural,
			);
		}

		/**
		 * Persist a new point type (mirrors myCRED_Settings_Module::sanitize_settings).
		 *
		 * @param string $key      Sanitized point type key.
		 * @param string $plural   Plural display name.
		 * @param string $singular Singular display name.
		 * @return true|WP_Error
		 */
		private function create_point_type( $key, $plural, $singular ) {
			$type_settings = mycred_get_option( 'mycred_pref_core_' . $key );
			if ( ! empty( $type_settings ) ) {
				return new WP_Error( 'point_type_exists', __( 'A point type with this key already exists.', 'mycred' ) );
			}

			$defaults = mycred()->defaults();
			$defaults['cred_id']              = $key;
			$defaults['name']['singular']     = $singular;
			$defaults['name']['plural']       = $plural;

			$saved = mycred_update_option( 'mycred_pref_core_' . $key, $defaults );
			if ( ! $saved ) {
				return new WP_Error( 'create_failed', __( 'Could not save point type settings.', 'mycred' ) );
			}

			$types           = mycred_get_option( 'mycred_types', array( MYCRED_DEFAULT_TYPE_KEY => mycred_label() ) );
			$types[ $key ]   = $plural;
			$types_saved     = mycred_update_option( 'mycred_types', $types );
			if ( ! $types_saved ) {
				return new WP_Error( 'create_failed', __( 'Could not update the point types registry.', 'mycred' ) );
			}

			if ( function_exists( 'mycred_upload_default_point_image' ) ) {
				mycred_upload_default_point_image();
			}

			return true;
		}

		/**
		 * Ability Callback: Suggest Ranks
		 */
		public function execute_suggest_ranks( $input ) {
			if ( ! $this->is_ranks_addon_active() ) {
				return new WP_Error( 'ranks_inactive', __( 'The Ranks addon is not enabled.', 'mycred' ) );
			}

			$site_type  = isset( $input['site_type'] ) ? sanitize_text_field( $input['site_type'] ) : 'Community';
			$point_type = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;

			if ( function_exists( 'mycred_point_type_exists' ) && ! mycred_point_type_exists( $point_type ) ) {
				$point_type = MYCRED_DEFAULT_TYPE_KEY;
			}

			return array(
				'suggested_ranks' => $this->get_rank_suggestions_for_site_type( $site_type ),
				'point_type'      => $point_type,
			);
		}

		/**
		 * Ability Callback: Create Ranks
		 */
		public function execute_create_ranks( $input ) {
			if ( ! $this->is_ranks_addon_active() ) {
				return new WP_Error( 'ranks_inactive', __( 'The Ranks addon is not enabled.', 'mycred' ) );
			}

			$ranks      = isset( $input['ranks'] ) && is_array( $input['ranks'] ) ? $input['ranks'] : array();
			$point_type = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;

			if ( empty( $ranks ) ) {
				return new WP_Error( 'no_ranks', __( 'No ranks provided to create.', 'mycred' ) );
			}

			if ( function_exists( 'mycred_point_type_exists' ) && ! mycred_point_type_exists( $point_type ) ) {
				return new WP_Error( 'invalid_point_type', __( 'The specified point type does not exist.', 'mycred' ) );
			}

			$created = array();
			$errors  = array();

			foreach ( $ranks as $rank ) {
				if ( empty( $rank['title'] ) ) {
					continue;
				}

				$result = $this->create_rank(
					sanitize_text_field( $rank['title'] ),
					isset( $rank['minimum'] ) ? (float) $rank['minimum'] : 0,
					isset( $rank['maximum'] ) ? (float) $rank['maximum'] : 0,
					$point_type
				);

				if ( is_wp_error( $result ) ) {
					$errors[] = $result->get_error_message();
					continue;
				}

				$created[] = $result;
			}

			if ( empty( $created ) ) {
				$message = ! empty( $errors ) ? implode( ' ', $errors ) : __( 'No ranks could be created.', 'mycred' );
				return new WP_Error( 'create_failed', $message );
			}

			if ( function_exists( 'mycred_assign_ranks' ) ) {
				mycred_assign_ranks( $point_type );
			}

			wp_cache_delete( 'ranks-published-' . $point_type, MYCRED_SLUG );
			wp_cache_delete( 'ranks-published-count-' . $point_type, MYCRED_SLUG );

			return array(
				'success'       => true,
				'message'       => sprintf(
					__( 'Successfully created %1$d rank(s) for point type %2$s.', 'mycred' ),
					count( $created ),
					$point_type
				),
				'created_ranks' => $created,
			);
		}

		/**
		 * Ability Callback: Suggest Badges
		 */
		public function execute_suggest_badges( $input ) {
			if ( ! $this->is_badges_addon_active() ) {
				return new WP_Error( 'badges_inactive', __( 'The Badges addon is not enabled.', 'mycred' ) );
			}

			$site_type  = isset( $input['site_type'] ) ? sanitize_text_field( $input['site_type'] ) : 'Community';
			$point_type = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;

			if ( function_exists( 'mycred_point_type_exists' ) && ! mycred_point_type_exists( $point_type ) ) {
				$point_type = MYCRED_DEFAULT_TYPE_KEY;
			}

			return array(
				'suggested_badges' => $this->get_badge_suggestions_for_site_type( $site_type, $point_type ),
				'point_type'       => $point_type,
			);
		}

		/**
		 * Ability Callback: Create Badges
		 */
		public function execute_create_badges( $input ) {
			if ( ! $this->is_badges_addon_active() ) {
				return new WP_Error( 'badges_inactive', __( 'The Badges addon is not enabled.', 'mycred' ) );
			}

			$badges     = isset( $input['badges'] ) && is_array( $input['badges'] ) ? $input['badges'] : array();
			$point_type = isset( $input['point_type'] ) ? sanitize_text_field( $input['point_type'] ) : MYCRED_DEFAULT_TYPE_KEY;

			if ( empty( $badges ) ) {
				return new WP_Error( 'no_badges', __( 'No badges provided to create.', 'mycred' ) );
			}

			if ( function_exists( 'mycred_point_type_exists' ) && ! mycred_point_type_exists( $point_type ) ) {
				return new WP_Error( 'invalid_point_type', __( 'The specified point type does not exist.', 'mycred' ) );
			}

			$created = array();
			$errors  = array();

			foreach ( $badges as $badge ) {
				if ( empty( $badge['title'] ) ) {
					continue;
				}

				$manual = ! empty( $badge['manual'] );
				$result = $this->create_badge(
					sanitize_text_field( $badge['title'] ),
					isset( $badge['description'] ) ? sanitize_text_field( $badge['description'] ) : '',
					$manual,
					$point_type,
					$manual ? '' : ( isset( $badge['reference'] ) ? sanitize_key( $badge['reference'] ) : '' ),
					isset( $badge['amount'] ) ? sanitize_text_field( $badge['amount'] ) : '',
					isset( $badge['by'] ) ? sanitize_key( $badge['by'] ) : 'count'
				);

				if ( is_wp_error( $result ) ) {
					$errors[] = $result->get_error_message();
					continue;
				}

				$created[] = $result;
			}

			if ( empty( $created ) ) {
				$message = ! empty( $errors ) ? implode( ' ', $errors ) : __( 'No badges could be created.', 'mycred' );
				return new WP_Error( 'create_failed', $message );
			}

			return array(
				'success'        => true,
				'message'        => sprintf( __( 'Successfully created %d badge(s).', 'mycred' ), count( $created ) ),
				'created_badges' => $created,
			);
		}

		/**
		 * Rank name/threshold templates by site type.
		 *
		 * @param string $site_type Site category label.
		 * @return array[]
		 */
		protected function get_rank_suggestions_for_site_type( $site_type ) {
			$key = strtolower( preg_replace( '/[^a-z0-9]+/', '', $site_type ) );

			$templates = array(
				'community' => array(
					array( 'title' => 'Newbie', 'minimum' => 0, 'maximum' => 99, 'description' => 'Just joined the community.' ),
					array( 'title' => 'Member', 'minimum' => 100, 'maximum' => 499, 'description' => 'Regular participating member.' ),
					array( 'title' => 'Contributor', 'minimum' => 500, 'maximum' => 1999, 'description' => 'Active contributor to discussions and content.' ),
					array( 'title' => 'Leader', 'minimum' => 2000, 'maximum' => 9999, 'description' => 'Highly engaged community leader.' ),
					array( 'title' => 'Legend', 'minimum' => 10000, 'maximum' => 9999999, 'description' => 'Top-tier community veteran.' ),
				),
				'buddyboss' => array(
					array( 'title' => 'Rookie', 'minimum' => 0, 'maximum' => 99, 'description' => 'New community member.' ),
					array( 'title' => 'Insider', 'minimum' => 100, 'maximum' => 499, 'description' => 'Growing social presence.' ),
					array( 'title' => 'Influencer', 'minimum' => 500, 'maximum' => 1999, 'description' => 'Recognized group participant.' ),
					array( 'title' => 'Ambassador', 'minimum' => 2000, 'maximum' => 9999, 'description' => 'Trusted community voice.' ),
					array( 'title' => 'Icon', 'minimum' => 10000, 'maximum' => 9999999, 'description' => 'Elite community status.' ),
				),
				'lms' => array(
					array( 'title' => 'Beginner', 'minimum' => 0, 'maximum' => 49, 'description' => 'Started learning on the platform.' ),
					array( 'title' => 'Student', 'minimum' => 50, 'maximum' => 199, 'description' => 'Making steady course progress.' ),
					array( 'title' => 'Scholar', 'minimum' => 200, 'maximum' => 999, 'description' => 'Completed multiple lessons or courses.' ),
					array( 'title' => 'Graduate', 'minimum' => 1000, 'maximum' => 4999, 'description' => 'Advanced learner with strong completion record.' ),
					array( 'title' => 'Expert', 'minimum' => 5000, 'maximum' => 9999999, 'description' => 'Master-level learner.' ),
				),
				'ecommerce' => array(
					array( 'title' => 'Bronze Shopper', 'minimum' => 0, 'maximum' => 499, 'description' => 'Early-stage customer.' ),
					array( 'title' => 'Silver Shopper', 'minimum' => 500, 'maximum' => 1999, 'description' => 'Repeat buyer.' ),
					array( 'title' => 'Gold Shopper', 'minimum' => 2000, 'maximum' => 9999, 'description' => 'Valued loyal customer.' ),
					array( 'title' => 'Platinum VIP', 'minimum' => 10000, 'maximum' => 9999999, 'description' => 'Top spending VIP.' ),
				),
				'blog' => array(
					array( 'title' => 'Reader', 'minimum' => 0, 'maximum' => 49, 'description' => 'Casual blog visitor.' ),
					array( 'title' => 'Commenter', 'minimum' => 50, 'maximum' => 199, 'description' => 'Engages via comments.' ),
					array( 'title' => 'Author', 'minimum' => 200, 'maximum' => 999, 'description' => 'Publishes or contributes content.' ),
					array( 'title' => 'Influencer', 'minimum' => 1000, 'maximum' => 9999999, 'description' => 'Highly active blog community member.' ),
				),
			);

			if ( isset( $templates[ $key ] ) ) {
				return $templates[ $key ];
			}

			if ( strpos( $key, 'buddy' ) !== false || strpos( $key, 'boss' ) !== false ) {
				return $templates['buddyboss'];
			}
			if ( strpos( $key, 'learn' ) !== false || strpos( $key, 'course' ) !== false || $key === 'lms' ) {
				return $templates['lms'];
			}
			if ( strpos( $key, 'commerce' ) !== false || strpos( $key, 'woo' ) !== false || strpos( $key, 'shop' ) !== false ) {
				return $templates['ecommerce'];
			}
			if ( strpos( $key, 'blog' ) !== false ) {
				return $templates['blog'];
			}

			return $templates['community'];
		}

		/**
		 * Badge templates by site type.
		 *
		 * @param string $site_type  Site category label.
		 * @param string $point_type Point type key for automatic requirements.
		 * @return array[]
		 */
		protected function get_badge_suggestions_for_site_type( $site_type, $point_type = MYCRED_DEFAULT_TYPE_KEY ) {
			$key = strtolower( preg_replace( '/[^a-z0-9]+/', '', $site_type ) );

			$community_badges = array(
				array(
					'title'       => 'Welcome Aboard',
					'description' => 'Awarded when a user registers on the community.',
					'manual'      => false,
					'reference'   => 'registration',
					'amount'      => '1',
					'by'          => 'count',
				),
				array(
					'title'       => 'Daily Visitor',
					'description' => 'Logs in regularly to stay active.',
					'manual'      => false,
					'reference'   => 'logging_in',
					'amount'      => '10',
					'by'          => 'count',
				),
				array(
					'title'       => 'Conversation Starter',
					'description' => 'Leaves thoughtful comments on posts.',
					'manual'      => false,
					'reference'   => 'comments',
					'amount'      => '25',
					'by'          => 'count',
				),
				array(
					'title'       => 'Content Creator',
					'description' => 'Publishes posts or pages for the community.',
					'manual'      => false,
					'reference'   => 'publishing_content',
					'amount'      => '5',
					'by'          => 'count',
				),
				array(
					'title'       => 'Community Champion',
					'description' => 'Manually awarded to standout members.',
					'manual'      => true,
					'reference'   => '',
					'amount'      => '',
					'by'          => '',
				),
			);

			$templates = array(
				'community' => $community_badges,
				'buddyboss' => array_merge( $community_badges, array(
					array(
						'title'       => 'Profile Complete',
						'description' => 'Uploaded an avatar or completed profile.',
						'manual'      => true,
						'reference'   => '',
						'amount'      => '',
						'by'          => '',
					),
				) ),
				'lms' => array(
					array( 'title' => 'First Lesson', 'description' => 'Started the learning journey.', 'manual' => false, 'reference' => 'registration', 'amount' => '1', 'by' => 'count' ),
					array( 'title' => 'Dedicated Learner', 'description' => 'Logs in consistently.', 'manual' => false, 'reference' => 'logging_in', 'amount' => '15', 'by' => 'count' ),
					array( 'title' => 'Course Contributor', 'description' => 'Publishes learning-related content.', 'manual' => false, 'reference' => 'publishing_content', 'amount' => '3', 'by' => 'count' ),
					array( 'title' => 'Honor Roll', 'description' => 'Manually awarded top students.', 'manual' => true, 'reference' => '', 'amount' => '', 'by' => '' ),
				),
				'ecommerce' => array(
					array( 'title' => 'First Purchase', 'description' => 'Completed a first order.', 'manual' => true, 'reference' => '', 'amount' => '', 'by' => '' ),
					array( 'title' => 'Loyal Customer', 'description' => 'Frequent logins and engagement.', 'manual' => false, 'reference' => 'logging_in', 'amount' => '20', 'by' => 'count' ),
					array( 'title' => 'Reviewer', 'description' => 'Leaves product or post reviews.', 'manual' => false, 'reference' => 'comments', 'amount' => '10', 'by' => 'count' ),
				),
				'blog' => array(
					array( 'title' => 'New Subscriber', 'description' => 'Registered on the blog.', 'manual' => false, 'reference' => 'registration', 'amount' => '1', 'by' => 'count' ),
					array( 'title' => 'Regular Reader', 'description' => 'Visits the site often.', 'manual' => false, 'reference' => 'site_visit', 'amount' => '7', 'by' => 'count' ),
					array( 'title' => 'Prolific Commenter', 'description' => 'Active in discussions.', 'manual' => false, 'reference' => 'comments', 'amount' => '15', 'by' => 'count' ),
				),
			);

			if ( isset( $templates[ $key ] ) ) {
				return $templates[ $key ];
			}

			if ( strpos( $key, 'buddy' ) !== false || strpos( $key, 'boss' ) !== false ) {
				return $templates['buddyboss'];
			}
			if ( strpos( $key, 'learn' ) !== false || strpos( $key, 'course' ) !== false || $key === 'lms' ) {
				return $templates['lms'];
			}
			if ( strpos( $key, 'commerce' ) !== false || strpos( $key, 'woo' ) !== false || strpos( $key, 'shop' ) !== false ) {
				return $templates['ecommerce'];
			}
			if ( strpos( $key, 'blog' ) !== false ) {
				return $templates['blog'];
			}

			return $templates['community'];
		}

		/**
		 * Create a single rank post.
		 *
		 * @param string $title      Rank title.
		 * @param float  $minimum    Min points.
		 * @param float  $maximum    Max points.
		 * @param string $point_type Point type key.
		 * @return array|WP_Error
		 */
		private function create_rank( $title, $minimum, $maximum, $point_type ) {
			if ( ! defined( 'MYCRED_RANK_KEY' ) ) {
				return new WP_Error( 'ranks_unavailable', __( 'Ranks are not available.', 'mycred' ) );
			}

			$existing = get_page_by_title( $title, OBJECT, MYCRED_RANK_KEY );
			if ( $existing && isset( $existing->ID ) ) {
				return new WP_Error( 'rank_exists', sprintf( __( 'A rank titled "%s" already exists.', 'mycred' ), $title ) );
			}

			$rank_id = wp_insert_post( array(
				'post_title'  => $title,
				'post_type'   => MYCRED_RANK_KEY,
				'post_status' => 'publish',
			), true );

			if ( is_wp_error( $rank_id ) || ! $rank_id ) {
				return new WP_Error( 'create_failed', sprintf( __( 'Could not create rank "%s".', 'mycred' ), $title ) );
			}

			$mycred = mycred( $point_type );
			$minimum = $mycred->number( $minimum );
			$maximum = $mycred->number( $maximum );

			mycred_update_post_meta( $rank_id, 'ctype', $point_type );
			mycred_update_post_meta( $rank_id, 'mycred_rank_min', $minimum );
			mycred_update_post_meta( $rank_id, 'mycred_rank_max', $maximum );

			return array(
				'rank_id' => (int) $rank_id,
				'title'   => $title,
				'minimum' => (float) $minimum,
				'maximum' => (float) $maximum,
			);
		}

		/**
		 * Create a single badge post.
		 *
		 * @param string $title       Badge title.
		 * @param string $description Post excerpt/content description.
		 * @param bool   $manual      Manual badge flag.
		 * @param string $point_type  Point type for requirements.
		 * @param string $reference   Hook reference for auto badges.
		 * @param string $amount      Requirement amount.
		 * @param string $by          Requirement by (count, sum, etc.).
		 * @return array|WP_Error
		 */
		private function create_badge( $title, $description, $manual, $point_type, $reference = '', $amount = '', $by = 'count' ) {
			if ( ! defined( 'MYCRED_BADGE_KEY' ) ) {
				return new WP_Error( 'badges_unavailable', __( 'Badges are not available.', 'mycred' ) );
			}

			$existing = get_page_by_title( $title, OBJECT, MYCRED_BADGE_KEY );
			if ( $existing && isset( $existing->ID ) ) {
				return new WP_Error( 'badge_exists', sprintf( __( 'A badge titled "%s" already exists.', 'mycred' ), $title ) );
			}

			$badge_id = wp_insert_post( array(
				'post_title'   => $title,
				'post_content' => $description,
				'post_type'    => MYCRED_BADGE_KEY,
				'post_status'  => 'publish',
			), true );

			if ( is_wp_error( $badge_id ) || ! $badge_id ) {
				return new WP_Error( 'create_failed', sprintf( __( 'Could not create badge "%s".', 'mycred' ), $title ) );
			}

			$requires = array();
			if ( ! $manual && ! empty( $reference ) ) {
				$requires[] = array(
					'type'      => $point_type,
					'reference' => $reference,
					'amount'    => $amount !== '' ? $amount : '1',
					'by'        => $by !== '' ? $by : 'count',
					'specific'  => '',
				);
			}

			$badge_levels = array(
				array(
					'attachment_id' => 0,
					'image_url'     => '',
					'label'         => __( 'Level 1', 'mycred' ),
					'compare'       => 'AND',
					'requires'      => $requires,
					'reward'        => array(
						'type'   => '',
						'log'    => '',
						'amount' => '',
					),
				),
			);

			mycred_update_post_meta( $badge_id, 'badge_prefs', $badge_levels );
			mycred_update_post_meta( $badge_id, 'manual_badge', $manual ? 1 : 0 );
			mycred_update_post_meta( $badge_id, 'open_badge', 0 );
			mycred_update_post_meta( $badge_id, 'congratulation_msg', sprintf( __( 'Congratulations! You earned the %s badge.', 'mycred' ), $title ) );
			mycred_update_post_meta( $badge_id, 'main_image', '' );
			mycred_update_post_meta( $badge_id, 'mycred_badge_align', 'mycred_align_none' );
			mycred_update_post_meta( $badge_id, 'mycred_layout_check', 'mycred_layout_none' );

			if ( function_exists( 'mycred_delete_option' ) ) {
				mycred_delete_option( 'mycred-badge-refs-' . $point_type );
			}

			return array(
				'badge_id' => (int) $badge_id,
				'title'    => $title,
				'manual'   => (bool) $manual,
			);
		}

		/**
		 * Build supplemental system instruction for ranks/badges abilities.
		 *
		 * @return string
		 */
		protected function get_ranks_badges_system_instruction() {
			$parts = array();

			if ( $this->is_ranks_addon_active() ) {
				$parts[] = 'For rank systems: when a user describes their site (e.g. community site) and wants ranks, use `mycred/suggest-ranks` with their site_type, present the suggested rank names and min/max point values clearly, ask for confirmation, then use `mycred/create-ranks` only after they agree. Ranks use post titles and point thresholds (minimum/maximum).';
			}

			if ( $this->is_badges_addon_active() ) {
				$parts[] = 'For badges: when a user wants achievement badges, use `mycred/suggest-badges` with site_type, explain each badge (manual vs automatic), ask for confirmation, then use `mycred/create-badges` after they agree. Set manual=true for admin-awarded badges; for automatic badges include reference (e.g. logging_in, registration, comments) and amount.';
			}

			return implode( ' ', $parts );
		}

		/**
		 * Helper to resolve user ID by username, slug, email, or numeric ID
		 *
		 * @param string|int $identifier The user identifier.
		 * @return int|bool User ID or false.
		 */
		private function resolve_user_id( $identifier ) {
			if ( empty( $identifier ) ) {
				return false;
			}

			$user = false;

			// Try as user ID
			if ( is_numeric( $identifier ) ) {
				$user = get_user_by( 'id', (int) $identifier );
			}

			// Try as email
			if ( ! $user && is_email( $identifier ) ) {
				$user = get_user_by( 'email', $identifier );
			}

			// Try as slug / username
			if ( ! $user ) {
				$user = get_user_by( 'slug', $identifier );
			}
			if ( ! $user ) {
				$user = get_user_by( 'login', $identifier );
			}

			return $user ? (int) $user->ID : false;
		}

		/**
		 * Admin URL for WordPress AI provider / API key settings (Connectors screen).
		 *
		 * @return string
		 */
		public function get_ai_connectors_settings_url() {
			return admin_url( 'options-connectors.php' );
		}

		/**
		 * User-facing message when no AI provider or API key is configured.
		 *
		 * @return string HTML message.
		 */
		public function get_ai_not_configured_message_html() {
			$settings_url = $this->get_ai_connectors_settings_url();

			return sprintf(
				/* translators: 1: opening anchor tag, 2: closing anchor tag */
				__( '<strong>AI Assistant is not connected yet.</strong> To use chat, install a WordPress AI provider plugin (if needed), then open %1$sSettings → Connectors%2$s and add your API key. After saving, return here and try again.', 'mycred' ),
				'<a href="' . esc_url( $settings_url ) . '">',
				'</a>'
			);
		}

		/**
		 * Map technical AI Core errors to messages suitable for site administrators.
		 *
		 * @param string|WP_Error $error Raw error from the AI client.
		 * @return array{message: string, is_html: bool}
		 */
		public function format_ai_error_for_user( $error ) {
			$code    = '';
			$message = '';

			if ( is_wp_error( $error ) ) {
				$code    = $error->get_error_code();
				$message = $error->get_error_message();
			} else {
				$message = (string) $error;
			}

			$haystack = strtolower( $code . ' ' . $message );

			$config_patterns = array(
				'no models found',
				'text_generation',
				'prompt_invalid_argument',
				'prompt_prevented',
				'not supported in this environment',
				'api key',
				'api_key',
				'authentication required',
				'unauthorized',
				'connector',
			);

			foreach ( $config_patterns as $pattern ) {
				if ( str_contains( $haystack, $pattern ) ) {
					return array(
						'message' => $this->get_ai_not_configured_message_html(),
						'is_html' => true,
					);
				}
			}

			return array(
				'message' => __( 'Sorry, the AI Assistant could not complete your request. Please try again in a moment.', 'mycred' ),
				'is_html' => false,
			);
		}

		/**
		 * AJAX Chat Controller
		 *
		 * Uses WordPress 7.0 AI Core Prompt Builder and Ability resolver in a recursive execution loop.
		 */
		public function handle_chat_ajax() {
			// Security checks
			check_ajax_referer( 'mycred_ai_chat_nonce', 'nonce' );

			if ( ! $this->permission_callback( null ) ) {
				wp_send_json_error( array( 'message' => __( 'Forbidden: insufficient permissions.', 'mycred' ) ) );
			}

			$user_message = isset( $_POST['message'] ) ? sanitize_text_field( $_POST['message'] ) : '';
			if ( empty( $user_message ) ) {
				wp_send_json_error( array( 'message' => __( 'Message cannot be empty.', 'mycred' ) ) );
			}

			// Deserialize history messages from payload
			$history_messages = array();
			if ( ! empty( $_POST['history'] ) && is_array( $_POST['history'] ) ) {
				foreach ( $_POST['history'] as $msg_arr ) {
					$msg_arr = wp_unslash( $msg_arr );
					if ( is_array( $msg_arr ) && isset( $msg_arr['role'] ) && isset( $msg_arr['parts'] ) ) {
						try {
							$history_messages[] = \WordPress\AiClient\Messages\DTO\Message::fromArray( $msg_arr );
						} catch ( \Exception $e ) {
							// Skip invalid history messages silently
						}
					}
				}
			}

			// Construct UserMessage DTO for the active user query and append it to the history list
			try {
				$user_message_obj = \WordPress\AiClient\Messages\DTO\Message::fromArray( array(
					'role'  => 'user',
					'parts' => array(
						array(
							'channel' => 'content',
							'type'    => 'text',
							'text'    => $user_message,
						),
					),
				) );
				$history_messages[] = $user_message_obj;
			} catch ( \Exception $e ) {
				wp_send_json_error( array( 'message' => __( 'Could not process user message.', 'mycred' ) ) );
			}

			$types_str = '';
			if ( function_exists( 'mycred_get_types' ) ) {
				$types = mycred_get_types();
				foreach ( $types as $slug => $label ) {
					$types_str .= "{$label} (Slug: '{$slug}'), ";
				}
				$types_str = rtrim( $types_str, ', ' );
			} else {
				$types_str = "Points (Slug: 'mycred_default')";
			}

			$chat_abilities = $this->get_chat_abilities();

			// Construct rich system prompts to guide the AI on context and formatting
			$system_instruction = "You are the myCred AI Assistant, an expert tool to help manage myCred point balances on this WordPress site. " .
			                      "You have access to several abilities: retrieving user balances, getting site summaries (like points in circulation), " .
			                      "awarding/deducting points, creating new point types, and setting up myCred Hooks. " .
			                      "Be helpful, precise, and professional. Always use the registered abilities to perform tasks instead of guessing or pretending. " .
			                      "When answering, display balances and statistics using the formatted values returned from the tools. " .
			                      "Only logged-in administrators have access to your chat, so you can execute the mutation tools (like award or deduct points, or create point types) when they ask you to. " .
			                      "When a user asks to create a new point type, use `mycred/create-point-type` with a lowercase meta key (letters and underscores only; spaces and hyphens become underscores), a plural name, and an optional singular name. Confirm the key and names with the user before creating. Tell them to reload the admin page after creation so the new type appears in menus. " .
			                      "When a user asks for hook recommendations (e.g., based on their site type like BuddyBoss or eCommerce), use `mycred/suggest-hooks` to fetch available hooks. " .
			                      "Then, recommend these hooks with dynamic point amount suggestions you deem appropriate for their site. Ask the user if they want to apply them. " .
			                      "If they confirm, use `mycred/apply-hooks` to save the configuration. " .
			                      $this->get_ranks_badges_system_instruction() .
			                      " IMPORTANT: The currently active point types on this site are: {$types_str}. When calling an ability that accepts a 'point_type' parameter, you MUST use one of these slugs. If the user asks to award/deduct a specific point type, use its corresponding slug. If the user does not specify, you can assume the default point type (mycred_default).";

			// Initialize the AI Core prompt builder with abilities and history
			$prompt = wp_ai_client_prompt()
				->using_system_instruction( $system_instruction )
				->with_history( ...$history_messages )
				->using_abilities( ...$chat_abilities );

			// Ability resolver configuration
			$resolver = new WP_AI_Client_Ability_Function_Resolver( ...$chat_abilities );

			if ( ! $prompt->is_supported_for_text_generation() ) {
				$formatted = $this->format_ai_error_for_user(
					new WP_Error(
						'prompt_invalid_argument',
						'No models found that support text_generation for this prompt.'
					)
				);
				wp_send_json_error( $formatted );
			}

			$loop_count = 0;
			$max_loops  = 5;

			while ( $loop_count < $max_loops ) {
				$result = $prompt->generate_result();

				if ( is_wp_error( $result ) ) {
					wp_send_json_error( $this->format_ai_error_for_user( $result ) );
				}

				$message            = $result->toMessage();
				$history_messages[] = $message;

				// Check if the model requested function execution (Abilities calls)
				if ( $resolver->has_ability_calls( $message ) ) {
					$response_message   = $resolver->execute_abilities( $message );
					$history_messages[] = $response_message;

					// Re-initialize prompt builder with the updated history and ability responses
					$prompt = wp_ai_client_prompt()
						->using_system_instruction( $system_instruction )
						->with_history( ...$history_messages )
						->using_abilities( ...$chat_abilities );

					$loop_count++;
					continue;
				}

				// No abilities called - this is the final chat response!
				wp_send_json_success( array(
					'reply'   => $result->toText(),
					'history' => array_map( function( $msg ) {
						return $msg->toArray();
					}, $history_messages )
				) );
			}

			wp_send_json_error( array( 'message' => __( 'Maximum resolution loop count exceeded.', 'mycred' ) ) );
		}

		/**
		 * Planned abilities shown in the sidebar (not registered yet).
		 *
		 * @return array<int, array{slug: string, label: string, icon: string, hint: string}>
		 */
		public function get_upcoming_abilities() {
			return array(
				array(
					'slug'  => 'mycred/setup-ecommerce',
					'label' => __( 'WooCommerce setup', 'mycred' ),
					'icon'  => 'dashicons-cart',
					'hint'  => __( 'Product rewards, checkout points & store hooks', 'mycred' ),
				),
				array(
					'slug'  => 'mycred/setup-gamification',
					'label' => __( 'Full gamification plan', 'mycred' ),
					'icon'  => 'dashicons-performance',
					'hint'  => __( 'AI roadmap for hooks, ranks, badges & point types', 'mycred' ),
				),
				array(
					'slug'  => 'mycred/setup-coupons',
					'label' => __( 'Coupons & rewards', 'mycred' ),
					'icon'  => 'dashicons-tickets-alt',
					'hint'  => __( 'Suggest and create point coupon campaigns', 'mycred' ),
				),
				array(
					'slug'  => 'mycred/setup-referrals',
					'label' => __( 'Referral program', 'mycred' ),
					'icon'  => 'dashicons-groups',
					'hint'  => __( 'Invite rewards and referral hook configuration', 'mycred' ),
				),
				array(
					'slug'  => 'mycred/setup-email-notices',
					'label' => __( 'Email notices', 'mycred' ),
					'icon'  => 'dashicons-email',
					'hint'  => __( 'Point event emails for users and admins', 'mycred' ),
				),
				array(
					'slug'  => 'mycred/analyze-leaderboard',
					'label' => __( 'Leaderboard insights', 'mycred' ),
					'icon'  => 'dashicons-visibility',
					'hint'  => __( 'Top users, circulation trends & engagement stats', 'mycred' ),
				),
				array(
					'slug'  => 'mycred/bulk-adjust-points',
					'label' => __( 'Bulk point adjust', 'mycred' ),
					'icon'  => 'dashicons-admin-users',
					'hint'  => __( 'Award or deduct points for many users at once', 'mycred' ),
				),
				array(
					'slug'  => 'mycred/setup-buycred',
					'label' => __( 'Buy points (buyCred)', 'mycred' ),
					'icon'  => 'dashicons-money',
					'hint'  => __( 'Gateways and packages to sell points', 'mycred' ),
				),
				array(
					'slug'  => 'mycred/setup-sell-content',
					'label' => __( 'Sell content', 'mycred' ),
					'icon'  => 'dashicons-lock',
					'hint'  => __( 'Paywall posts and pages with point prices', 'mycred' ),
				),
				array(
					'slug'  => 'mycred/audit-point-log',
					'label' => __( 'Log & fraud audit', 'mycred' ),
					'icon'  => 'dashicons-shield',
					'hint'  => __( 'Spot unusual balances and suspicious activity', 'mycred' ),
				),
			);
		}

		/**
		 * Admin Screen View Renderer
		 */
		public function admin_page() {
			$mycred_ai_ranks_active     = $this->is_ranks_addon_active();
			$mycred_ai_badges_active    = $this->is_badges_addon_active();
			$mycred_ai_avatar_url       = $this->get_ai_avatar_url();
			$mycred_ai_upcoming_abilities = $this->get_upcoming_abilities();

			$view_path = plugin_dir_path( __FILE__ ) . 'views/admin-chat-page.php';
			if ( file_exists( $view_path ) ) {
				include $view_path;
			} else {
				echo '<div class="notice notice-error"><p>' . __( 'AI Assistant view file is missing.', 'mycred' ) . '</p></div>';
			}
		}

	}

endif;
