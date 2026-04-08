<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MYCRED_Toolkit_li_IMPORTER' )) {

	/**
	 * myCRED Learndash Importer class
	 **/
	class MYCRED_Toolkit_li_IMPORTER {

		/**
		 * Construct
		 **/
		public function __construct() {
			$this->mycred_li_define_constants();
			$this->mycred_li_init();
		}

		/**
		 * mycred learndash check define path
		 **/
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * mycred learndash define constants
		 **/
		private function mycred_li_define_constants() {
			$this->define( 'MYCRED_LI_PREFIX', 'mycred_li_' );
			$this->define( 'mycred_li_slug', 'mycred-toolkit');
			$this->define( 'mycred_li', __FILE__ );
			$this->define( 'mycred_li_root_dir', plugin_dir_path(mycred_li) );
			$this->define( 'mycred_li_includes_dir', mycred_li_root_dir . 'page/' );
		}

		/**
		 * mycred learndash initialize
		 **/
		private function mycred_li_init() {
			add_action('admin_enqueue_scripts', array( $this, 'mycred_li_enqueue' ));
			add_action( 'admin_notices', array( $this, 'mycred_li_required_plugin_notices' ) );
			add_action('mycred_after_core_prefs', array( $this, 'mycred_li_learndash_importer_page' ));
			add_action('wp_ajax_mycred_learndash_points_importer_import', array( $this, 'mycred_learndash_points_importer_import' ));
		}

		/**
		 * Enqueue Style and Scripts
		 */
		public function mycred_li_enqueue() {
			wp_enqueue_script( MYCRED_LI_PREFIX . 'custom_script', plugin_dir_url( __FILE__ ) . 'assets/js/custom.js', '', 1.0 );
			wp_enqueue_style( MYCRED_LI_PREFIX . 'stylesheet', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', '', 1.0 );
		}
		

		/**
		 * Returns the last import date
		 * @param $category
		 * @return bool|mixed|string|void
		 */
		public function mycred_li_get_last_import( $category ) {
			$result = !empty(get_option($category)) ? get_option($category) : 'Not Imported Yet.';
			return $result;
		}

		public function mycred_li_get_class( $category ) {
			return !empty(get_option($category)) ? 'li-not-empty' : 'li-empty';
		}
		/**
		 * render form
		 */
		public function mycred_li_learndash_importer_page() {

			$userpointtypes = mycred_get_types();

			$html = '';
			

		$html .= '
            <div class="mycred-ui-accordion">
                <div class="mycred-ui-accordion-header">
                    <h4 class="mycred-ui-accordion-header-title">
                        <span class="dashicons dashicons-database-import mycred-ui-accordion-header-icon"></span>
                        <strong>LearnDash</strong> Importer
                    </h4>
                    <div class="mycred-ui-accordion-header-actions hide-if-no-js">
                        <button type="button" aria-expanded="true">
                            <span class="mycred-ui-toggle-indicator" aria-hidden="true"></span>
                        </button>
                    </div>
                </div>
                <div class="body mycred-ui-accordion-body" style="display:none;">
                    <div class="row">
                        <div class="col-sm-2">
                            <label for="mycred_learndash_points_importer_points_type" class="mycred_learndash_label">Points type to import</label>
                        </div>
                        <div class="col-sm-4">
                            <select class="form-control learndash_select" name="mycred_learndash_points_importer_points_type" id="mycred_learndash_points_importer_points_type">';  
			foreach ( $userpointtypes as $key => $value ) {
				$html .= '<option value="' . $key . '">' . $value . '</option>';
			}
				
					$html .= '</select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-2">
                            <label for="gamipress_learndash_points_importer_workflow" class="mycred_learndash_label">How should user points balances be imported?</label>
                        </div>
                        <div class="col-sm-10">
                            <select name="mycred_learndash_points_importer_workflow" id="mycred_learndash_points_importer_workflow" class="form-control">
                                <option value="sum">Sum Learndash and myCred Point Balances (User balance)</option>
                                <option value="override">Override myCred points with LearnDash points</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">     
                            <div style="margin: 10px 0;">
                                <button style="padding: 6px !important;" name="mycred_learndash_points_importer_run" id="mycred_learndash_points_importer_run" class="li_import_button button button-primary li_import_points_types " value="li_import_points_types">
                                    <span class="li_icon dashicons dashicons-star-filled"></span>
                                    <label class="mycred-learndash-importer-label">Import Learndash Points</label>
                                </button>
                            </div> 
                        </div>
                    </div>       
                </div>
            </div>';

			echo wp_kses( $html, 
					array( 
						'div'   => array( 
							'class' => array(),
							'style' => array() 
						),
						'span'  => array(
							'class' => array()

						),
						'strong' => array(),
						'button' => array(
							'class' => array(),
							'type'  => array(),
							'name'  => array(),
							'id'    => array(),
							'value' => array(),
							'style' => array()
						),
						'h4'    => array(
							'class' => array()
						),
						'label' => array(
							'class' => array(),
							'for'   => array()
						),
						'select' => array(
							'class' => array(),
							'name'  => array(),
							'id'    => array()
						),
						'option' => array(
							'value' => array(),
						)
					) 
				);
		}

		/**
		 * 
		 **/
		public function mycred_learndash_points_importer_import() {
			
			global $wpdb; 

			if ( ! isset( $_POST['points_type'] ) || empty( $_POST['points_type'] ) ) {
				wp_send_json_error( __( 'Please, choose a points type.', 'mycred-toolkit' ) );
			}
		
			if ( ! isset( $_POST['workflow'] ) || empty( $_POST['workflow'] ) ) {
				wp_send_json_error( __( 'Please, choose the way this tool should work.', 'mycred-toolkit' ) );
			}

			$points_types = mycred_get_types();

			$points_type = sanitize_text_field(wp_unslash($_POST['points_type']));

			$workflow = sanitize_text_field(wp_unslash($_POST['workflow']));

			if ( ! isset( $points_types[ $points_type ] ) ) {
				wp_send_json_error( __( 'Choose a valid points type.', 'mycred-toolkit' ) );
			}

			$loop = ( ! isset( $_POST['loop'] ) ? 0 : absint( $_POST['loop'] ) );
			$limit = 100;
			$offset = $limit * $loop;
			$run_again = false;

			ignore_user_abort( true );

			// Get all stored users
			$users = $wpdb->get_results( "SELECT u.ID FROM {$wpdb->users} AS u ORDER BY u.ID ASC LIMIT {$offset}, {$limit}" );

			// Return a success message if finished, else run again
			if ( empty( $users ) && $loop !== 0 ) {
				wp_send_json_success( __( 'Import process finished successfully.', 'mycred-toolkit' ) );
			} else {
				$run_again = true;
			}

			if ( empty( $users ) ) {
				wp_send_json_error( __( 'Could not find users.', 'mycred-toolkit' ) );
			}

			// Let's to bulk revoke
			foreach ( $users as $user ) {

				$learndash_points = learndash_get_user_course_points( $user->ID ) ;

				$mycredpoints = mycred_get_users_balance( $user->ID );

				$totalpoints = $learndash_points;

				if ( $workflow === 'override' ) {

					mycred_subtract( 'override_learndash_points', $user->ID, $mycredpoints, 'Overriding myCred points with LearnDash points', null, '', $points_type  );

				}

				mycred_add( 'learndash_points', $user->ID, $totalpoints, 'Sum of Learndash and myCred Point Balances', null, '', $points_type );

			}

			if ( $run_again ) {

				$awarded_users = $limit * ( $loop + 1 );
		
				$users_count = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users} AS u ORDER BY u.ID ASC" ) );
		
				$remaining_users = $users_count - $awarded_users;
		
				// Return a run again message (just when revoking to all users)
				wp_send_json_success( array(
					'run_again' => $run_again,
					// Translators: %d is the number of remaining users.
					'message' => sprintf( __( '%d remaining users', 'mycred-toolkit' ), ( $remaining_users > 0 ? $remaining_users : 0 ) ),
				) );
		
			} else {
				// Return a success message
				wp_send_json_success( __( 'Import process finished successfully.', 'mycred-toolkit' ) );
			}
		}

		/**
		 * mycred learndash required plugin notices
		 **/
		public function mycred_li_required_plugin_notices() {

			$msg = __( 'need to be active and installed to use myCred learndash importer plugin.', 'mycred-toolkit' );

			if ( !is_plugin_active('mycred/mycred.php') ) {
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/mycred/">%1$s</a> %2$s</p></div>', esc_html_e( 'myCred', 'mycred-toolkit' ), esc_html( $msg ) );
			}
		}
	}
	//end class
}

new MYCRED_Toolkit_li_IMPORTER();
