<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCred_frm' ) ) :
	final class myCred_frm {

		// Instnace
		protected static $_instance = NULL;

		// Current session
		public $session             = NULL;

		/**
		 * Setup Instance
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 */
		public function __clone() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Not allowed
		 */
		public function __wakeup() { _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', $this->version ); }

		/**
		 * Define
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) )
				define( $name, $value );
		}

		/**
		 * Require File
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) )
				require_once $required_file;
		}

		/**
		 * Construct
		 */
		public function __construct() {

			// if ( class_exists( 'myCRED_Core' ) && class_exists( 'Forminator' ) ) {

				$this->define_constants();
				$this->includes();
				add_filter( 'mycred_setup_hooks',    array( $this, 'register_hooks' ) );
				add_filter( 'mycred_all_references', array( $this, 'setup_references' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) ); 
			// } 
		}

		/**
		 * Define Constants
		 * First, we start with defining all required constants if they are not defined already.
		 */
		private function define_constants() {

			$this->define( 'MYCRED_FRM',              __FILE__ );
			$this->define( 'MYCRED_FRM_ROOT_DIR',     plugin_dir_path( MYCRED_FRM ) );
			$this->define( 'MYCRED_FRM_ASSETS', plugin_dir_url( MYCRED_FRM)  . 'assets/' );

		}

		/**
		 * Enqueue related admin scripts
		 */
		public function admin_scripts() {

			wp_register_script( 'mycred-frm-admin-script', MYCRED_FRM_ASSETS . 'js/mycred-frm-admin.js', array('jquery'), 1.0 );
			wp_enqueue_script( 'mycred-frm-admin-script' );

		}

		/**
		 * Include Plugin Files
		 */
		public function includes() {

			$this->file( MYCRED_FRM_ROOT_DIR . 'includes/functions.php' );
			$this->file( MYCRED_FRM_ROOT_DIR . 'includes/mycred-frm-form.php' );
			$this->file( MYCRED_FRM_ROOT_DIR . 'includes/mycred-frm-poll.php' );
			$this->file( MYCRED_FRM_ROOT_DIR . 'includes/mycred-frm-quiz.php' );

		}

		/**
		 * Register the plugin's mycred hooks
		 */

		public function register_hooks( $installed )
		{

			$installed['frm_form_submit'] = array(
				'title'       => __( 'Points for Forminator Forms', 'mycred-frm' ),
				'description' => __( 'Award points for submitting Forminator Forms.', 'mycred-frm' ),
				'callback'    => array( 'myCRED_FRM_Form' )
			);
			$installed['frm_quiz_submit'] = array(
				'title'       => __( 'Points for Forminator Quizzes', 'mycred-frm' ),
				'description' => __( 'Award points for submitting Forminator Quizzes.', 'mycred-frm' ),
				'callback'    => array( 'myCRED_frm_Quiz' )
			);

			$installed['frm_poll_submit'] = array(
				'title'       => __( 'Points for Forminator Polls', 'mycred-frm' ),
				'description' => __( 'Award points for submitting Forminator Polls.', 'mycred-frm' ),
				'callback'    => array( 'myCRED_frm_Poll' )
			);
			
			return $installed;
		}

		/**
		 * Setup references so they show on the Edit Points log screen
		 */
		function setup_references( $references ) {

			$references['frm_submit_frm']	= 'Forminator: Submit a Form';
			$references['frm_submit_spec_frm']	= 'Forminator: Submit a Specific Form';
			$references['frm_submit_spec_field_frm']	= 'Forminator: Submit a Specific field value on any form';
			$references['frm_submit_spec_field_spec_frm']	= 'Forminator: Submit Specific field value in Specific Form';

			$references['frm_vote_on_poll']	= 'Forminator: Vote on a Poll';
			$references['frm_vote_on_spec_poll']	= 'Forminator: Vote on a Specific Poll';
			$references['frm_submit_spec_field_poll']	= 'Forminator: Submit a Specific field value on any Poll';
			$references['frm_submit_spec_field_spec_poll']	= 'Forminator: Submit a Specific field value in Specific Poll';

			$references['frm_submit_quiz']	= 'Forminator: Submit a Quiz';
			$references['frm_submit_spec_quiz']	= 'Forminator: Submit a Specific Quiz';
			$references['frm_pass_a_quiz']	= 'Forminator: Pass a Quiz';
			$references['frm_pass_a_specific_quiz']	= 'Forminator: Pass a Specific Quiz';
			$references['frm_fail_a_quiz']	= 'Forminator: Fail a Quiz';
			$references['frm_fail_a_specific_quiz']	= 'Forminator: Fail a Specific Quiz';
			$references['frm_submit_spec_field_quiz']	= 'Forminator: Submit a Specific field value on any Quiz';
			$references['frm_submit_spec_field_spec_quiz']	= 'Forminator: Submit a Specific field value on a Specific Quiz';

			return $references;

		}


	}
endif;

function init_myCred_frm() {
	return myCred_frm::instance();
}

add_action( 'mycred_init', 'init_myCred_frm' );


function myCred_frm_admin_notice() {

	if ( !class_exists( 'myCRED_Core' ) || !class_exists( 'Forminator' ) ) {
		$message = __( 'myCred Forminator require myCred and Forminator in order to work!!', 'mycred-forminator' );
		echo '<div class="notice notice-error"><p>' . $message . '</p></div>';
	}

}
add_action( 'admin_notices', 'myCred_frm_admin_notice' );