<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'myCRED_Badgr_Settings' ) ) :
	class myCRED_Badgr_Settings extends myCRED_Module {

		private static $_instance;

		/**
		 * myCRED_Badgr_Settings constructor.
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {
			add_action( 'mycred_after_core_prefs', array( $this, 'badgr_settings' ) );
			add_action( 'wp_ajax_mycred_badgr_admin', array( $this, 'mycred_badgr_admin' ) );
			add_action( 'wp_ajax_nopriv_badgr-login-disconnect', array( $this, 'badgr_login_disconnect' ) );
			add_action( 'wp_ajax_badgr-login-disconnect', array( $this, 'badgr_login_disconnect' ) );
			add_action( 'wp_ajax_nopriv_mycred_badgr_admin', array( $this, 'mycred_badgr_admin' ) );
			add_filter( 'mycred_save_core_prefs', array( $this, 'sanitize_extra_settings' ), 10, 3 );
			add_action( 'mycred_save_badge', array( $this, 'create_badge' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}

		/**
		 * Logins admin make admin issuer on Badgr
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_badgr_admin() {
			$email =  sanitize_text_field( $_REQUEST['user_name'] );

			$password = sanitize_text_field( $_REQUEST['user_password'] );

			mycred_br_login_and_auth( $email, $password );
		}

		/**
		 * @return mixed
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
		 * Generates Uninstall Menu In myCred Settings
		 * @since 1.0
		 * @version 1.0
		 */
		public function badgr_settings() {
			?>
		<div class="mycred-ui-accordion">
			<div class="mycred-ui-accordion-header">
				<h4 class="mycred-ui-accordion-header-title">
				<span class="dashicons dashicons-awards mycred-ui-accordion-header-icon"></span>
				<?php esc_html_e( 'Badgr', 'mycred-toolkit' ); ?>
			</h4>
			<div class="mycred-ui-accordion-header-actions hide-if-no-js">
				<button type="button" aria-expanded="true">
					<span class="mycred-ui-toggle-indicator" aria-hidden="true"></span>
				</button>
			</div>
			</div>
			<div class="body mycred-ui-accordion-body" style="display:none;">
				<div class="row">
					<h3><?php esc_html_e( 'Badgr Logins', 'mycred-toolkit' ); ?></h3>
					<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
						<div class="form-group">
							<label><?php esc_html_e( 'Email Address', 'mycred-toolkit' ); ?></label>
							<input type="email" id="badgr-email" 
							<?php
							if ( $this->has_refresh_token() ) {
echo 'disabled';
							} else {
'';
							}
							?>
							name="mycred_pref_core[badgr][admin_email]" value="<?php echo esc_attr( $this->get_prefs( 'admin_email' ) ); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12" 
					<?php
					if ( $this->has_refresh_token() ) {
echo 'style="display:none;"';}
					?>
					>
						<div class="form-group">
							<label><?php esc_html_e( 'Password', 'mycred-toolkit' ); ?></label>
							<input type="password" id="badgr-password" name="mycred_pref_core[badgr][password]" class="form-control" />
						</div>
					</div>	
					<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
						<div class="form-group">
							<label class="mycred-badgr-login"><?php esc_html_e( 'Badgr Login', 'mycred-toolkit' ); ?></label>
							<div>
								<input type="hidden" id="badgr-access-token" name="mycred_pref_core[badgr][access_token]" />
								<input type="hidden" id="badgr-refresh-token" name="mycred_pref_core[badgr][refresh_token]" />		
								<input type="hidden" id="badgr-entit-id" name="mycred_pref_core[badgr][entity_id]" />		
								<input type="hidden" id="issuer-entity-id" name="mycred_pref_core[badgr][issuer_entity_id]" />	
								<button class="button button-large large button-primary"  id="
								<?php
								if ( $this->has_refresh_token() ) {
echo 'badgr-login-disconnect';
								} else {
echo 'badgr-login';
								}
								?>
								" name="mycred_pref_core[badgr][badgr_login]"> 
									<span class="dashicons dashicons-update mycred-switch-all-badges-icon"></span>
									<?php
									if ( $this->has_refresh_token() ) {
echo 'Disconnect';
									} else {
echo 'Sync Badge';
									}
									?>
								</button>
							</div>
						</div>
					</div>
				</div>
				<p><?php esc_html_e( 'Your password won\'t be saved in Database.', 'mycred-toolkit' ); ?></p>
				<h3 id="response-message">
				<?php
				if ( $this->has_refresh_token() ) {
echo 'Connected !';
				} else {
'';
				}
				?>
				</h3>
			</div>
		</div>
		<?php
		}

		/**
		 * Creates badge on Badgr
		 * @param $badge_id
		 * @since 1.0
		 * @version 1.0
		 */
		public function create_badge( $badge_id ) {
			$name = get_the_title( $badge_id );

			$badge = mycred_get_badge( $badge_id );

			//If not open badge return
			if ( !$badge->open_badge ) {
			return;
			}

			//If Badge already on Badgr return
			if ( get_post_meta( $badge_id, 'mycred_badgr_entity_id' ) ) {
			return;
			}

		
			$image = $badge->main_image_url;

			$post = get_post( $badge_id );
	
			$description = $post->post_content;

			$path = pathinfo( $image );

			$ext = mb_strtolower( $path['extension'] );

			$image =  file_get_contents( $image );      
		
			if ( in_array( $ext, array( 'png', 'svg' ) ) ) {
			$image =  'data:image/' . $ext . ';base64,' . base64_encode( $image );
	
			} elseif ( $image !== false ) {
			$image =  'data:image/png;base64,' . base64_encode( $image );
			}

			$criteria_url = get_permalink( $badge_id );

			$issuer_entity_id = $this->get_prefs( 'issuer_entity_id' );

			$access_token = $this->get_prefs( 'access_token' );

			$refresh_token = $this->get_prefs( 'refresh_token' );

			$response = '';
	
			//Creating Badge on Badgr
			$response = myCRED_Badgr_API::create_badge( $name, $description, $image, $criteria_url, $badge_id, $issuer_entity_id, $access_token );
	
			//Refreshing and Saving Tokens
			if ( $response['response']['code'] == 401 ) {
				$response = myCRED_Badgr_API::refresh_access_token( $refresh_token );

				$new_access_token = json_decode( $response['body'] )->access_token;

				$new_refresh_token = json_decode( $response['body']  )->refresh_token;

				$this->update_tokens( $new_access_token, $new_refresh_token  );

				$access_token = $this->get_prefs( 'access_token' );

				$refresh_token = $this->get_prefs( 'refresh_token' );

				$response = myCRED_Badgr_API::create_badge( $name, $description, $image, $criteria_url, $badge_id, $issuer_entity_id, $access_token );
			}
		

			//Saving badgr Entity ID
			if ( $response['response']['code'] == 201 ) {
				update_post_meta( $badge_id, 'mycred_badgr_entity_id', json_decode( $response['body'] )->result[0]->entityId );
				add_filter( 'redirect_post_location', array( $this, 'add_success_notice_query' ), 99 );
			} else {
				add_filter( 'redirect_post_location', array( $this, 'add_unsuccess_notice_query' ), 99 );
			}
		}

		/**
		 * Disconnects users login
		 * @since 1.0
		 * @version 1.0
		 */
		public function badgr_login_disconnect() {
			$this->update_prefs( 'admin_email', '' );
			$this->update_prefs( 'access_token', '' );
			$this->update_prefs( 'refresh_token', '' );
			$this->update_prefs( 'entity_id', '' );
			$this->update_prefs( 'issuer_entity_id', '' );
		}

		/**
		 * Checks if admin has refresh token or not
		 * @return bool
		 * @since 1.0
		 * @version 1.0
		 */
		public function has_refresh_token() {
			if ( !empty( $this->get_prefs( 'refresh_token' ) ) ) {
			return true;
			}

			return false;
		}

		/**
		 * Updates refresh and access token in DB
		 * @param $access_token
		 * @param $refresh_token
		 * @return bool
		 * @since 1.0
		 * @version 1.0
		 */
		public function update_tokens( $access_token, $refresh_token ) {
			$prefs = mycred_get_option( 'mycred_pref_core', false );

			if ( !in_array( 'badgr', $prefs ) ) {
			return false;
			}

			$prefs['badgr']['access_token'] = $access_token;
			$prefs['badgr']['refresh_token'] = $refresh_token;
		
			mycred_update_option( 'mycred_pref_core', $prefs );

			return true;
		}


		/**
		 * Get admin prefs
		 * @param $key
		 * @return bool
		 * @since 1.0
		 * @version 1.0
		 */
		public function get_prefs( $key ) {
			$hooks = mycred_get_option( 'mycred_pref_core', false );

			if ( is_array( $hooks ) && in_array( $key, $hooks ) ) {
				if ( array_key_exists( 'badgr', $hooks ) && array_key_exists( $key, $hooks['badgr'] ) ) {
					return $hooks['badgr'][ $key ];
				}
			}

			return '';
		}

		/**
		 * Updates admin prefs
		 * @param $key
		 * @param $value
		 * @since 1.0
		 * @version 1.0
		 */
		public function update_prefs( $key, $value ) {
			$prefs = mycred_get_option( 'mycred_pref_core', false );

			if ( !in_array( 'badgr', $prefs ) ) {
			return false;
			}

			$prefs['badgr'][ $key ] = $value;
		
			mycred_update_option( 'mycred_pref_core', $prefs );

			return true;
		}

		/**
		 * Sanitizes and saves settings
		 * @param $new_data
		 * @param $data
		 * @param $core
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public function sanitize_extra_settings( $new_data, $data, $core ) {
			if ( array_key_exists( 'badgr', $data ) ) {
				$new_data['badgr']['admin_email'] = ( isset( $data['badgr']['admin_email'] ) ) ? sanitize_text_field( $data['badgr']['admin_email'] ) : '';
				$new_data['badgr']['access_token'] = ( isset( $data['badgr']['access_token'] ) ) ? sanitize_text_field( $data['badgr']['access_token'] ) : '';
				$new_data['badgr']['refresh_token'] = ( isset( $data['badgr']['refresh_token'] ) ) ? sanitize_text_field( $data['badgr']['refresh_token'] ) : '';
				$new_data['badgr']['entity_id'] = ( isset( $data['badgr']['entity_id'] ) ) ? sanitize_text_field( $data['badgr']['entity_id'] ) : '';
				$new_data['badgr']['issuer_entity_id'] = ( isset( $data['badgr']['issuer_entity_id'] ) ) ? sanitize_text_field( $data['badgr']['issuer_entity_id'] ) : '';

			}

			return $new_data;
		}

		/**
		 * Successfull Query URL
		 * @param $location
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_success_notice_query( $location ) {
			remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );

			return add_query_arg( array( 'badgr_resp' => 'success' ), $location );
		}

		/**
		 * Unsuccessfull Query URL
		 * @param $location
		 * @return mixed
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_unsuccess_notice_query( $location ) {
			remove_filter( 'redirect_post_location', array( $this, 'add_notice_query_var' ), 99 );

			return add_query_arg( array( 'badgr_resp' => 'unsuccess' ), $location );
		}

		/**
		 * Admin Notices
		 * @since 1.0
		 * @version 1.0
		 */
		public function admin_notices() {
			if ( isset( $_GET['badgr_resp'] ) ) {
				if ( $_GET['badgr_resp'] == 'success' ) {
					?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Badge Synced with Badgr.', 'mycred-toolkit' ); ?></p>
				</div>
				<?php
				}
				if ( $_GET['badgr_resp'] == 'unsuccess' ) {
					?>
				<div class="notice notice-error is-dismissible">
					<p><?php esc_html_e( 'Something went wrong, Required fileds: Badge Name, Description, Default Badge Image (PNG/ SVG).', 'mycred-toolkit' ); ?></p>
				</div>
				<?php
				}
			}
		}
	}
endif;