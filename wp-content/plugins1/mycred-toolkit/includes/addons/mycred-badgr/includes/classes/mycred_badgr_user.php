<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'myCRED_Badgr_user' ) ) :
	class myCRED_Badgr_user {

		/**
		 * Holds the instance of class
		 * @var $_instance
		 * @since 1.0
		 * @version 1.0
		 */
		private static $_instance;

		/**
		 * Holds the User's ID
		 * @var int|mixed|string
		 * @since 1.0
		 * @version 1.0
		 */
		private $user_id;

		/**
		 * Single-ton class initializer
		 * @return myCRED_Badgr_user
		 * @since 1.0
		 * @version 1.0
		 */
		public static function get_instance() {
			if ( self::$_instance == null ) {
			self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * myCRED_Badgr_user constructor.
		 * @param string $user_id
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct( $user_id = '' ) {
			$user_id = $user_id == '' ? get_current_user_id() : $user_id;
		
			$this->user_id = $user_id;
		}

		/**
		 * Checks whether user has refresh token or not
		 * @return bool
		 * @since 1.0
		 * @version 1.0
		 */
		public function has_refresh_token() {
			$user_data = mycred_get_user_meta( $this->user_id, 'mycred_badgr_data' );

			if ( !$user_data || empty( $user_data ) ) {
			return false;
			}

			if ( array_key_exists( 'refresh_token', $user_data[0] ) ) {
			return true;
			}
		}

		/**
		 * Update User's Access and Refresh token
		 * @param $access_token
		 * @param $refresh_token
		 * @since 1.0
		 * @version 1.0
		 */
		public function update_tokens( $access_token, $refresh_token ) {
			$user_data = mycred_get_user_meta( $this->user_id, 'mycred_badgr_data' );

			$user_data[0]['access_token'] = $access_token;

			$user_data[0]['refresh_token'] = $refresh_token;

			mycred_update_user_meta( $this->user_id, 'mycred_badgr_data', '', $user_data[0] );
		}

		/**
		 * Gets User's preference/ Data
		 * @param $key
		 * @return false|mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_prefs( $key ) {
			$user_data = mycred_get_user_meta( $this->user_id, 'mycred_badgr_data' );

			if ( !$user_data || empty( $user_data ) ) {
			return false;
			}

			$user_data = mycred_get_user_meta( $this->user_id, 'mycred_badgr_data' );

			return $user_data[0][ $key ];
		}
	}
endif;

myCRED_Badgr_user::get_instance();
