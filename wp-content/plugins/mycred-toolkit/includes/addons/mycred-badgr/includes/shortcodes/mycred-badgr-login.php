<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'myCRED_Badgr_Login' ) ) :
	class myCRED_Badgr_Login {

		/**
		 * Holds Current Classes Instance
		 * @var $_instance
		 * @since 1.0
		 * @version 1.0
		 */
		private static $_instance;

		/**
		 * Holds User's Data
		 * @var myCRED_Badgr_user
		 * @since 1.0
		 * @version 1.0
		 */
		private $user;

		/**
		 * Holds User's Data
		 * @var int
		 * @since 1.0
		 * @version 1.0
		 */
		private $user_id;

		/**
		 * Badgr Admin settings
		 * @var myCRED_Badgr_Settings
		 * @since 1.0
		 * @version 1.0
		 */
		private $admin_settings;

		/**
		 * Single-ton Class Initializer
		 * @return myCRED_Badgr_Login
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
		 * myCRED_Badgr_Login constructor.
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {
			add_shortcode( 'mycred_badgr_login', array( $this, 'mycred_badgr_login' ) );

			add_action( 'wp_ajax_mycred-badgr-user-login', array( $this, 'mycred_badgr_user_login' ) );

			add_action( 'wp_ajax_nopriv_mycred-badgr-user-login', array( $this, 'mycred_badgr_user_login' ) );
		
			add_action( 'wp_ajax_mycred-badgr-save-logins', array( $this, 'mycred_badgr_save_logins' ) );

			add_action( 'wp_ajax_nopriv_mycred-badgr-save-logins', array( $this, 'mycred_badgr_save_logins' ) );

			add_action( 'wp_ajax_mycred-badgr-user-login-disconnect', array( $this, 'mycred_badgr_user_login_disconnect' ) );

			add_action( 'wp_ajax_nopriv_mycred-badgr-user-login-disconnect', array( $this, 'mycred_badgr_user_login_disconnect' ) );

			add_action( 'wp_ajax_mycred-badgr-user-sync', array( $this, 'mycred_badgr_user_sync' ) );

			add_action( 'wp_ajax_nopriv_mycred-badgr-user-sync', array( $this, 'mycred_badgr_user_sync' ) );

			$this->user_id = get_current_user_id();

			$this->user = new myCRED_Badgr_user( $this->user_id );

			$this->admin_settings = new myCRED_Badgr_Settings();
		}

		/**
		 * Renders User login shortcode
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_badgr_login() {
			$content = '';
			$has_refresh_token = $this->user->has_refresh_token() ? 'disabled' : '';
			$email_value = $this->user->get_prefs( 'email' ) ? ' value=' . $this->user->get_prefs( 'email' ) : '';
			$pass_has_refresh_token = $this->user->has_refresh_token() ? 'style="display: none;"' : '';
			$button_has_refrest_token = $this->user->has_refresh_token() ? 'mycred-badgr-login-disconnect' : 'mycred-badgr-login';
			$login_dc = $this->user->has_refresh_token() ? 'Disconnect' : 'Login';
			$response_msg = $this->user->has_refresh_token() ? 'Connected!' : '';
			if ( is_user_logged_in() ) :
			$content .= "
        <form class='mycred-badgr-login'>
            <div>
                <label for='mycred-br-user-email'>Enter your Badgr email:</label>
            </div>
            <input $has_refresh_token $email_value type='email' name='email'  id='mycred-br-user-email'>
            <div $pass_has_refresh_token>
            <div>
                <label for='mycred-br-user-password' name='password'>Enter your Badgr password:</label>
            </div>
            <input type='password' id='mycred-br-user-password'>
            </div>
            <div>
            <br>
            <input type='hidden' id='badgr-access-token' name='access_token' />
            <input type='hidden' id='badgr-refresh-token' name='refresh_token' />		
            <input type='hidden' id='badgr-entit-id' name='entity_id' />			
            <button id=$button_has_refrest_token><span class='mycred-ajax-switch'></span>$login_dc</button>";
			
				if ( $this->user->has_refresh_token() ) { 
				$content .= "<button id='mycred-badgr-user-sync'><span class='mycred-ajax-switch'></span>Sync</button>";
				}
			
				$content .= "</div>
                <p>
                    Your password won't be saved in Database.
                </p>
                <p id='response-message'>$response_msg</p>
                <p>";
				if ( $this->get_user_last_sync() ) {
				$content .= 'Last Sync: ' . $this->get_user_last_sync();
				}

				$content .= '
                </p>
            </form>';
			endif;
			return $content;
		}

		/**
		 * User Login AJAX Callback
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_badgr_user_login() {
			$email = sanitize_email( $_REQUEST['email'] );

			$password = sanitize_text_field( $_REQUEST['password'] );

			$response = mycred_br_login_and_auth( $email, $password, false );
			die;
		}

		/**
		 * User logins save AJAX Callback
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_badgr_save_logins() {
			$mycred_badgr_data = array();

			$mycred_badgr_data['email'] = sanitize_email( $_REQUEST['email'] );

			$mycred_badgr_data['access_token'] = sanitize_text_field( $_REQUEST['access_token'] );

			$mycred_badgr_data['refresh_token'] = sanitize_text_field( $_REQUEST['refresh_token'] );

			$mycred_badgr_data['entity_id'] = sanitize_text_field( $_REQUEST['entity_id'] );

			mycred_update_user_meta( $this->user_id, 'mycred_badgr_data', '', $mycred_badgr_data );
			die;
		}

		/**
		 * Syncs User's Badges with Badgr
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_badgr_user_sync() {
			$user_data = get_userdata( $this->user_id );

			$user_access_token = $this->user->get_prefs( 'access_token' );

			$user_refresh_token = $this->user->get_prefs( 'refresh_token' );

			$user_email = $this->user->get_prefs( 'email' );

			$admin_access_token = $this->admin_settings->get_prefs( 'access_token' );

			$admin_refresh_token = $this->admin_settings->get_prefs( 'access_token' );

			$admin_entity_id = $this->admin_settings->get_prefs( 'entity_id' );

			$issuer_entity_id = $this->admin_settings->get_prefs( 'issuer_entity_id' );

			$recipient_email = $user_data->user_email;

			$earned_badges = mycred_get_users_badges( $this->user_id );

			$response = '';
		
			foreach ( $earned_badges as $badge_id => $value ) {
				$issued_badges = mycred_get_user_meta( $this->user_id, 'mycred_badgr_issued' )[0];

				$issued_on = mycred_get_user_meta( $this->user_id, MYCRED_BADGE_KEY . $badge_id, '_issued_on', true );

				$issued_on = date( DATE_ISO8601, $issued_on );

				$entity_id = mycred_get_post_meta( $badge_id, 'mycred_badgr_entity_id' )[0];

				if ( !empty( $entity_id ) && !in_array( $badge_id, $issued_badges ) ) {
	  
					$response = myCRED_Badgr_API::issue_assertion( $entity_id, $admin_entity_id, $issuer_entity_id, $admin_access_token, $recipient_email, $issued_on );

					//If unauthorized, Refresh admin access token
					if ( $response['response']['code'] == 401 ) {
						$response = myCRED_Badgr_API::refresh_access_token( $admin_refresh_token );

						$new_access_token = json_decode( $response['body'] )->access_token;

						$new_refresh_token = json_decode( $response['body']  )->refresh_token;

						$this->admin_settings->update_tokens( $new_access_token, $new_refresh_token  );

						$admin_access_token = $this->admin_settings->get_prefs( 'access_token' );

						$admin_refresh_token = $this->admin_settings->get_prefs( 'access_token' );

						$response = myCRED_Badgr_API::issue_assertion( $entity_id, $admin_entity_id, $issuer_entity_id, $admin_access_token, $recipient_email, $issued_on );

					}

					//If badge synced, save it to db
					if ( $response['response']['code'] == 201 ) {
						if ( empty( $issued_badges ) ) {
							mycred_update_user_meta( $this->user_id, 'mycred_badgr_issued', '', array( $badge_id ) );
						} else {
							array_push( $issued_badges, $badge_id );

							mycred_update_user_meta( $this->user_id, 'mycred_badgr_issued', '', $issued_badges );
						}
					}
			
				}

			}

			mycred_update_user_meta( $this->user_id, 'mycred_badgr_last_sync', '', date( 'd-m-Y' ) );

			die;
		}

		/**
		 * Gets User's Last sync time
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_user_last_sync() {
			if ( !empty( mycred_get_user_meta( $this->user_id, 'mycred_badgr_last_sync' ) ) ) {
			return mycred_get_user_meta( $this->user_id, 'mycred_badgr_last_sync' )[0];
			}
		}

		/**
		 * Disconencts Users login
		 * @return bool
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_badgr_user_login_disconnect() {
			return delete_user_meta( $this->user_id, 'mycred_badgr_data' );
		}
	}
endif;

myCRED_Badgr_Login::get_instance();
