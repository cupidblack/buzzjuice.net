<?php
/**
 * Class YITH_Role_Changer
 *
 * @package YITH\AutomaticRoleChanger\Classes
 */

defined( 'YITH_WCARC_VERSION' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_Role_Changer' ) ) {
	/**
	 * Class YITH_Role_Changer
	 */
	class YITH_Role_Changer {
		use YITH_WCARC_Extensible_Singleton_Trait;

		/**
		 * Plugin version
		 *
		 * @var string
		 * @since 1.0
		 */
		protected $version = YITH_WCARC_VERSION;

		/**
		 * Main Admin Instance
		 *
		 * @var YITH_Role_Changer_Admin
		 */
		public $admin;


		/**
		 * YITH_Role_Changer_Set_Roles Instance
		 *
		 * @var YITH_Role_Changer_Set_Roles
		 */
		public $set_roles;

		/**
		 * YITH_Role_Changer_Roles_Manager Instance
		 *
		 * @var YITH_Role_Changer_Roles_Manager
		 */
		public $roles_manager;


		/**
		 * Construct
		 *
		 * @since 1.0
		 */
		protected function __construct() {
			if ( is_admin() ) {
				$this->admin = YITH_Role_Changer_Admin::get_instance();
				if ( ! function_exists( 'members_plugin' ) ) {
					$this->roles_manager = YITH_Role_Changer_Roles_Manager::get_instance();
				}
			}

			$this->set_roles = YITH_Role_Changer_Set_Roles::get_instance();

			YITH_WCARC_REST_Server::get_instance();

			add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
			add_action( 'wp_loaded', array( $this, 'register_plugin_for_updates' ), 99 );
		}

		/**
		 * Register plugins for activation tab
		 */
		public function register_plugin_for_activation() {
			if ( function_exists( 'YIT_Plugin_Licence' ) ) {
				YIT_Plugin_Licence()->register( YITH_WCARC_INIT, YITH_WCARC_SECRETKEY, YITH_WCARC_SLUG );
			}
		}

		/**
		 * Register plugins for update tab
		 */
		public function register_plugin_for_updates() {
			if ( function_exists( 'YIT_Upgrade' ) ) {
				YIT_Upgrade()->register( YITH_WCARC_SLUG, YITH_WCARC_INIT );
			}
		}

		/**
		 * Class Initialization
		 *
		 * @deprecated 2.0
		 */
		public function init() {
		}
	}
}

if ( ! function_exists( 'yith_role_changer' ) ) {
	/**
	 * Unique access to instance of YITH_Role_Changer class
	 *
	 * @return YITH_Role_Changer
	 */
	function yith_role_changer() {
		return YITH_Role_Changer::get_instance();
	}
}
