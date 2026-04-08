<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

if ( ! class_exists('MyCRED_Credly_Badge') ) :
	
	class MyCRED_Credly_Badge extends myCRED_Badge_Module {

		public $credly_auth;
		public $credly_organization;

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.0
		 */
		public function __construct() {

			$prefs = mycred_get_option( 'mycred_pref_core' );
			$this->credly_auth = '';
			$this->credly_organization = '';

			if ( ! empty( $prefs ) ) {
				$this->credly_auth = ! empty( $prefs['credly']['access_token'] ) ? $prefs['credly']['access_token'] : '';
				$this->credly_organization = ! empty( $prefs['credly']['organization_id'] ) ? $prefs['credly']['organization_id'] : '';
			}

			add_action('admin_footer', array( $this, 'connect_existing_credly_badge' ) );
			add_action( 'wp_ajax_get-mycred-credly-badges-list', array( $this, 'get_mycred_credly_badges_list' ) );
			add_action( 'wp_ajax_sync_credly_badge', array( $this, 'connect_credly_badge' ) );
			add_action( 'mycred_after_badge_assign', array( $this, 'assign_credly_badge' ), 10, 3 );
			add_shortcode('mycred_credly_login', array( $this, 'mycred_credly_login_shortcode' ) );
			add_action( 'init', array( $this, 'mycred_credly_handle_form_submission' ) );
		}  

		public function connect_existing_credly_badge() {
			if ( isset( $_REQUEST['post_type'] ) && 'mycred_badge' === sanitize_text_field( $_REQUEST['post_type'] ) ) {
				add_thickbox();
				?>
			<script type="text/javascript">
				jQuery(document).ready(function() {
					jQuery(`<a href="#" id="mycred_credly_connect_badge">Import Credly Badge</a>`).insertAfter('.page-title-action');
				});
			</script>
			<div class="overlay-credly-modal" style="display:none;">
			<div id="mycred-credly-badge-modal">
				<div id="mycred-credly-badge-modal-wraper">
					<span class="close-modal-btn">&times;</span>
						<h2><?php esc_html_e('Connect Existing Credly Badge', 'mycred-toolkit'); ?></h2>
						<div id="mycred-credly-badge-container"></div>
					</div>
				</div>
			</div>
			<?php
			}
		}
		
		public function get_mycred_credly_badges_list() {

			if ( ! isset( $_POST['nonce'] ) || ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'], 'mycred_nounce_credly_badge_list') ) ) {
				$response = array(
					'status'  => 'error',
					'message' => __( 'Security check failed.', 'mycred-toolkit' )
				);
				wp_send_json_error( $response );
				wp_die();
			}
			
			if ( ! empty( $this->credly_auth && ! empty( $this->credly_organization ) ) ) {

				$credly_url = 'https://sandbox-api.credly.com/v1/organizations/' . $this->credly_organization . '/badge_templates';
			
				$credly_response = wp_remote_get( $credly_url, array(
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Basic ' . base64_encode( $this->credly_auth . ':' )
					)
				));  
				
				if ( ! is_wp_error( $credly_response ) ) {
					$response_data = json_decode( $credly_response['body'] );
				
					if ( isset( $response_data->data ) && is_array( $response_data->data ) && ! empty( $response_data->data ) ) {
						
						$response['data'] = $response_data->data; 
						wp_send_json_success( $response );
					}
				
				} else {
					$response['status'] = 'error';
					$response['message'] = 'Error retrieving data from Credly API';
					wp_send_json_error( $response );
					wp_die();
				}
			}
		}
		
		public function wp_insert_attachment_from_url( $url, $parent_post_id = null ) {

			if ( ! class_exists( 'WP_Http' ) ) {
				require_once ABSPATH . WPINC . '/class-http.php';
			}
	
			add_filter( 'upload_mimes', function ( $mimes ) {
				$mimes['svg'] = 'image/svg+xml'; 
				$mimes['json'] = 'application/json';
				$mimes['blob'] = 'application/octet-stream';
				return $mimes;
			});
	
			$http     = new WP_Http();
			$response = $http->request( $url );
		
			if ( 200 != $response['response']['code'] ) {
				return false;
			}
		
			$file_name = basename($url);

			if ($file_name === 'blob' || !$file_name) {
				$file_name = 'image_' . time() . '.png';
			}
			$upload = wp_upload_bits($file_name, null, $response['body']);
		
			if ( ! empty( $upload['error'] ) ) {   
				return false;
			}
	
			$file_path        = $upload['file'];
			$file_name        = basename( $file_path );
			$file_type        = wp_check_filetype( $file_name, null );
			$attachment_title = sanitize_file_name( pathinfo( $file_name, PATHINFO_FILENAME ) );
			$wp_upload_dir    = wp_upload_dir();
	
			$post_info = array(
				'guid'           => $wp_upload_dir['url'] . '/' . $file_name,
				'post_mime_type' => $file_type['type'],
				'post_title'     => $attachment_title,
				'post_content'   => '',
				'post_status'    => 'inherit',
			);
	
			$attach_id = wp_insert_attachment( $post_info, $file_path, $parent_post_id );
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
			wp_update_attachment_metadata( $attach_id, $attach_data );
		
			return $attach_id;
		}
		
		public function connect_credly_badge() {

			if ( ! isset( $_POST['nonce'] ) || ( isset( $_POST['nonce'] ) && ! wp_verify_nonce( $_POST['nonce'], 'mycred_nounce_credly_badge_list') ) ) {
				$response = array(
					'status'  => 'error',
					'message' => __( 'Security check failed.', 'mycred-toolkit' )
				);
				wp_send_json_error( $response );
				wp_die();
			}

			if ( ! empty( $_POST['badge_id'] ) &&  ! empty( $_POST['badge_title'] ) && ! empty( $_POST['badge_img'] ) && wp_http_validate_url( $_POST['badge_img'] ) && isset( $_POST['badge_desc'] ) ) {
		
				$badge_id    = sanitize_text_field( $_POST['badge_id'] );
				$badge_title = sanitize_text_field( $_POST['badge_title'] );
				$badge_img   = esc_url( $_POST['badge_img'] );
				$badge_desc  = sanitize_textarea_field( $_POST['badge_desc'] ); 
		
				$existing_badge = get_posts( array(
					'post_type'   => 'mycred_badge',
					'title'       => $badge_title,
					'post_status' => 'publish',
					'numberposts' => 1,
				));
		
				if ( ! empty( $existing_badge ) ) {
					$response = array(
						'status'  => 'error',
						'message' => __( 'Badge with this title already exists.', 'mycred-toolkit' )
					);
					wp_send_json_error( $response );
					wp_die();
				}
		
				$badge_img_id = $this->wp_insert_attachment_from_url( $badge_img );
		
				$mycred_connected_credly_badges = mycred_get_option( 'mycred_connected_credly_badges', array() );
		
				$badge_data = array(
					'post_title'   => $badge_title,
					'post_content' => $badge_desc,
					'post_status'  => 'publish', 
					'post_type'    => 'mycred_badge',
					'meta_input'   => array(
						'mycred_credly_badge_id'           => $badge_id,
						'mycred_credly_badge_description'  => $badge_desc
					)
				);
		
				$mycred_badge_id = wp_insert_post( $badge_data );
		
				if ( ! is_wp_error( $badge_img_id ) ) {
					update_post_meta( $mycred_badge_id, 'main_image', $badge_img_id );
				}
		
				array_push( $mycred_connected_credly_badges, $mycred_badge_id );
				mycred_update_option( 'mycred_connected_credly_badges', $mycred_connected_credly_badges );
		
				$response = array( 'status' => 'success' );
				wp_send_json_success( $response );
				wp_die();
		
			} else {
				$response = array(
					'status'  => 'error',
					'message' => __( 'Invalid input or missing fields.', 'mycred-toolkit' )
				);
				wp_send_json_error( $response );
				wp_die();
			}
		}      

		public function assign_credly_badge( $user_id, $badge_id, $level ) {

			$user_data = get_userdata($user_id);
			$template_id = get_post_meta( $badge_id, 'mycred_credly_badge_id', true );

			$credly_url = 'https://sandbox-api.credly.com/v1/organizations/' . $this->credly_organization . '/badges';
			$current_time = current_time('Y-m-d H:i:s');
			$issued_at = $current_time . ' -0500';

			$args = array(
				'recipient_email' => $user_data->data->user_email,
				'issued_to_first_name' => $user_data->display_name,
				'issued_to_last_name' => $user_data->display_name,  
				'badge_template_id' => $template_id, 
				'issued_at' => $issued_at
			);

			$credly_response = wp_remote_post($credly_url, array(
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Basic ' . base64_encode($this->credly_auth . ':')
				),
				'body' => json_encode($args), 
				'method' => 'POST'
			));
		}
	
		public function mycred_credly_login_shortcode() {
			wp_enqueue_style( 'style-mycred-credly' );
			
			if ( ! is_user_logged_in() ) {
				return '<div class="mycred-warning-message"><p><b>Warning!</b> You need to login first.</p></div>';
			}
		
			if ( ! session_id() ) {
				session_start(); 
			}
		
			$user = wp_get_current_user();
			$user_email = get_user_meta( $user->ID, 'mycred_credly_connected_email', true );
		
			ob_start();
			?>
			<div class="mycred-credly-login">
				<?php
				if ( isset( $_SESSION['mycred_message'] ) ) {
					echo $_SESSION['mycred_message'] ;
					unset( $_SESSION['mycred_message'] ); 
				}
				?>
		
				<?php if ( ! empty( $user_email ) ) : ?>
					<p>Your email address <?php echo esc_html( $user_email ); ?> is already connected with Credly.</p>
					<form id="disconnect-credly-form" method="post">
					<input type="hidden" name="nonce_disconnect" value="<?php echo esc_attr( wp_create_nonce( 'mycred_credly_disconnect_nonce' ) ); ?>"/>
						<button type="submit" name="disconnect_credly"><?php echo esc_html__( 'Disconnect Credly', 'mycred-toolkit' ); ?></button>
					</form>
				<?php else : ?>
					<form id="connect-credly-form" method="post">
						<label for="credly_email"><?php echo esc_html__( 'Enter Your Credly Email', 'mycred-toolkit' ); ?></label>
						<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'mycred_credly_nonce' ) ); ?>"/>
						<input type="email" id="credly_email" name="credly_email" placeholder="name@example.com" value="" required />
						<button type="submit" name="connect_credly"><?php echo esc_html__( 'Connect Credly', 'mycred-toolkit' ); ?></button>
					</form>
				<?php endif; ?>
			</div>
			<?php
			return ob_get_clean();
		}
		
		public function mycred_credly_handle_form_submission() {
			if ( ! session_id() ) {
				session_start();
			}
		
			if ( isset( $_POST['connect_credly'] ) ) {
				if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'mycred_credly_nonce' ) ) {
					wp_die( esc_html__( 'Nonce verification failed. Please try again.', 'mycred-toolkit' ) );
				} else {
					$credly_email = sanitize_email( $_POST['credly_email'] );
					if ( is_email( $credly_email ) ) {
						$user = wp_get_current_user();
						$prefs = mycred_get_option( 'mycred_credly_pref_core' );
						if ( ! is_array( $prefs ) ) {
						$prefs = array();
						}
						$prefs[] = $credly_email;
						mycred_update_option( 'mycred_credly_pref_core', $prefs );
						update_user_meta( $user->ID, 'mycred_credly_connected_email', $credly_email );
						wp_redirect( esc_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) ) );
						exit;
					} else {
						wp_die( esc_html__( 'Please enter a valid email address.', 'mycred-toolkit' ) );
					}
				}
			} elseif ( isset( $_POST['disconnect_credly'] ) ) {
				if ( ! isset( $_POST['nonce_disconnect'] ) || ! wp_verify_nonce( $_POST['nonce_disconnect'], 'mycred_credly_disconnect_nonce' ) ) {
					$_SESSION['mycred_message'] = '<div class="mycred-error-message"><p>' . esc_html__( 'Nonce verification failed. Please try again.', 'mycred-toolkit' ) . '</p></div>';
				} else {
					$user = wp_get_current_user();
					$user_email = get_user_meta( $user->ID, 'mycred_credly_connected_email', true );
					$prefs = mycred_get_option( 'mycred_credly_pref_core' );
					$key = array_search( $user_email, $prefs );
					if ( $key !== false ) {
						unset( $prefs[ $key ] );
						mycred_update_option( 'mycred_credly_pref_core', $prefs );
					}
					delete_user_meta( $user->ID, 'mycred_credly_connected_email' );
					$_SESSION['mycred_message'] = '<div class="mycred-success-message"><p>' . esc_html__( 'Your email has been successfully disconnected from Credly.', 'mycred-toolkit' ) . '</p></div>';
					wp_redirect( esc_url( add_query_arg( array(), $_SERVER['REQUEST_URI'] ) ) );
					exit;
				}
			}
		}
	}

	new MyCRED_Credly_Badge();

endif;