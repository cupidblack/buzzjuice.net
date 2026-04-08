<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * myCred-anspress core class
 * @package mycred-anspress
 * @since 1.0.0
 */
if ( ! class_exists( 'myCRED_Toolkit_Anspress_Core' ) ) :
	final class myCRED_Toolkit_Anspress_Core {

		
		/**
		 * Plugin Instance
		 *
		 * @var     $_instance  static  plugin's object instance
		 * @access  protected
		 * @since   1.0.0
		 */
		protected static $_instance = null;

		/**
		 * Setting Plugin Instance
		 * checks if the current instance is empty then
		 * initializes a new one else returns $_instance
		 *
		 * @access public
		 * @return object
		 * @since  1.0.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Defines Plugin Constants     All constants defined by this method
		 *
		 * @access private
		 *
		 * @param $name     string      constant name
		 * @param $value    string      constant value
		 *
		 * @since  1.0.0
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Require File for plugin      All files required using this method
		 *
		 * @param $required_file    string      required file path
		 *
		 * @throws Exception
		 * @since 1.0.0
		 */
		public function file( $required_file ) {
			if ( isset( $required_file ) ) {
				require_once $required_file;
			} else {
				throw new Exception( 'File Not Found' );
			}
		}

		/**
		 * myCRED_Anspress_Core Constructor
		 *
		 * @access  public
		 * @since   1.0.0
		 */
		public function __construct() {
			$this->define_constants();
			$this->init();
		}

		/**
		 * myCRED_Anspress_Core Initializer method
		 * holds all the actions and filter required
		 *
		 * @access  private
		 * @since   1.0.0
		 */
		private function init() {

			$this->file( ABSPATH . 'wp-admin/includes/plugin.php' );

			if ( is_plugin_active( 'mycred/mycred.php' ) && is_plugin_active( 'anspress-question-answer/anspress-question-answer.php' ) ) {
				$this->includes();
				add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets' ) );
				add_filter( 'mycred_setup_hooks', array( $this, 'register_hooks' ), 10, 2 );
				add_action( 'mycred_load_hooks', array( $this, 'load_hooks' ) );
				add_filter( 'mycred_all_references', array( $this, 'register_references' ) );

			}
			add_action( 'admin_notices', array( $this, 'required_plugin_notices' ) );
		}

		/**
		 * Plugin's Constants are defined in this method
		 *
		 * @access  private
		 * @since   1.1.1
		 */
		private function define_constants() {

	
			$this->define( 'MYCRED_ANSPRESS', __FILE__ );
			$this->define( 'MYCRED_ANSPRESS_ROOT_DIR', plugin_dir_path( MYCRED_ANSPRESS ) );
			$this->define( 'MYCRED_ANSPRESS_ASSETS_DIR_URL', plugin_dir_url( MYCRED_ANSPRESS ) . 'assets/' );
			$this->define( 'MYCRED_ANSPRESS_INCLUDES_DIR', MYCRED_ANSPRESS_ROOT_DIR . 'includes/' );
		}

		/**
		 * Include Plugin Files
		 *
		 * @since 1.1.1
		 */
		public function includes() {
			// $this->file( 'includes/mycred-anspress-functions.php' );
		}


		/**
		 * Registers new hooks in mycred
		 *
		 * @param $installed
		 *
		 * @return mixed
		 */
		public function register_hooks( $installed ) {
			// on asking a new question
			$installed['anspress_after_new_question'] = array(
				'title'       => __( '%plural% for asking a Anspress question', 'mycred-toolkit' ),
				'description' => __( 'Adds a myCRED hook for tracking points scored On asking anspress questions.', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Anspress_New_Question_Hook' )
			);

			// on answering a question
			$installed['anspress_after_new_answer'] = array(
				'title'       => __( '%plural% for a new answer in Anspress', 'mycred-toolkit' ),
				'description' => __( 'Adds a myCRED hook for tracking points scored On answering anspress questions.', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Anspress_New_Answer_Hook' )
			);

			// selecting best answer
			$installed['anspress_ap_select_answer'] = array(
				'title'       => __( '%plural% for selecting best answer in anspress', 'mycred-toolkit' ),
				'description' => __( 'Adds a myCRED hook for tracking points scored On selecting best answer', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Anspress_Best_Answer_Hook' )
			);

			// get answer selected as best
			$installed['anspress_ap_get_best_answer'] = array(
				'title'       => __( '%plural% for getting answer selected as best', 'mycred-toolkit' ),
				'description' => __( 'Adds a myCRED hook for tracking points scored On getting answer selected as best', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Anspress_Get_Best_Answer_Hook' )
			);

			// vote up
			$installed['anspress_vote_up'] = array(
				'title'       => __( '%plural% for voting up in Anspress.', 'mycred-toolkit' ),
				'description' => __( 'Adds a myCRED hook for tracking points scored On voting up in Anspress', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Anspress_Vote_Up_Hook' )
			);

			// vote up
			$installed['anspress_get_vote_up'] = array(
				'title'       => __( '%plural% for getting a vote up in Anspress.', 'mycred-toolkit' ),
				'description' => __( 'Adds a myCRED hook for tracking points scored On getting a vote up in Anspress', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Anspress_Gets_Vote_Up_Hook' )
			);

			// vote down
			$installed['anspress_vote_down'] = array(
				'title'       => __( '%plural% for voting down in Anspress.', 'mycred-toolkit' ),
				'description' => __( 'Adds a myCRED hook for tracking points scored On voting down in Anspress', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Anspress_Vote_Down_Hook' )
			);

			// vote down
			$installed['anspress_get_vote_down'] = array(
				'title'       => __( '%plural% for getting voting down in Anspress.', 'mycred-toolkit' ),
				'description' => __( 'Adds a myCRED hook for tracking points scored On gets voting down in Anspress', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Anspress_Get_Vote_Down_Hook' )
			);

			// publish comment
			$installed['anspress_publish_comment'] = array(
				'title'       => __( '%plural% for publishing comment in Anspress.', 'mycred-toolkit' ),
				'description' => __( 'Adds a myCRED hook for tracking points scored On publishing comment in Anspress', 'mycred-toolkit' ),
				'callback'    => array( 'myCRED_Anspress_Publish_Comment_Hook' )
			);

			return $installed;
		}

		/**
		 * Include Hook Files
		 *
		 * @since 1.1.1
		 */
		public function load_hooks() {

			$this->file( 'includes/mycred-anspress-new-question.php' );
			$this->file( 'includes/mycred-anspress-new-answer.php' );
			$this->file( 'includes/mycred-anspress-best-answer.php' );
			$this->file( 'includes/mycred-anspress-get-best-answer.php' );
			$this->file( 'includes/mycred-anspress-vote-up.php' );
			$this->file( 'includes/mycred-anspress-get-vote-up.php' );
			$this->file( 'includes/mycred-anspress-vote-down.php' );
			$this->file( 'includes/mycred-anspress-get-vote-down.php' );
			$this->file( 'includes/mycred-anspress-publish-comment.php' );
		}


		/**
		 * Registers Reference in myCred
		 *
		 * @param $list
		 *
		 * @return mixed
		 * @since 1.0.0
		 */
		public function register_references( $list ) {

			$list['anspress_after_new_question'] = __( 'Asking Anspress New Question', 'mycred-toolkit' );
			$list['anspress_ask_new_question']   = __( 'Answering Anspress Question', 'mycred-toolkit' );
			$list['anspress_ap_select_answer']   = __( 'Anspress selecting Best Answer', 'mycred-toolkit' );
			$list['anspress_vote_up']            = __( 'Anspress Vote Up', 'mycred-toolkit' );
			$list['anspress_get_vote_up']        = __( 'Anspress Gets a Vote Up', 'mycred-toolkit' );
			$list['anspress_get_vote_down']      = __( 'Anspress Gets a Vote down', 'mycred-toolkit' );
			$list['anspress_vote_down']          = __( 'Anspress Vote down', 'mycred-toolkit' );
			$list['anspress_publish_comment']    = __( 'Anspress Publish Comment', 'mycred-toolkit' );
			$list['anspress_ap_get_best_answer'] = __( 'Anspress Gets Best Answer', 'mycred-toolkit' );

			return $list;
		}

		/**
		 * Load Admin hooks
		 *
		 * @param string $hook
		 *
		 * @since  1.0.0
		 * @access pub
		 */
		public function load_admin_assets( $hook ) {
//          if ( is_mycred_hook_page( $hook ) ) {
//              wp_enqueue_script(
//                  'mycred_Anspress_admin_script',
//                  MYCRED_ANSPRESS_ASSETS_DIR_URL . 'js/script.js',
//                  array( 'jquery' ),
//                  '1.0'
//              );
//              wp_enqueue_style(
//                  'mycred_Anspress_admin_style',
//                  MYCRED_ANSPRESS_ASSETS_DIR_URL . 'css/style.css',
//                  array(),
//                  '1.0'
//              );
//          }
		}

		/**
		 * PLogin Notices
		 *
		 * @since 1.0.0
		 *
		 *
		 */
		public function required_plugin_notices() {

			$msg = esc_html__( 'need to be active and installed to use myCred Anspress plugin.', 'mycred-toolkit' );

			if ( ! is_plugin_active( 'mycred/mycred.php' ) ) {
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/mycred/">%1$s</a> %2$s</p></div>', esc_html__( 'myCred', 'mycred-toolkit' ), esc_html( $msg ) );
			}
			if ( ! is_plugin_active( 'anspress-question-answer/anspress-question-answer.php' ) ) {
				$anspress_msg = esc_html__( 'Anspress need to be active and installed to use myCred Anspress plugin.', 'mycred-toolkit' );
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/Anspress/">%1$s</a> %2$s</p></div>', esc_html__( 'Anspress', 'mycred-toolkit' ), esc_html( $msg ) );
			}
		}
	}
endif;

if ( ! function_exists( 'mycred_anspress_core' ) ) {
	function mycred_anspress_core() {
		return myCRED_Toolkit_Anspress_Core::instance();
	}
}
mycred_anspress_core();
