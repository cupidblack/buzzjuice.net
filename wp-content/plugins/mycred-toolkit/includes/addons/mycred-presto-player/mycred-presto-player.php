<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('myCRED_Presto_Player')):
	#[AllowDynamicProperties]
	final class myCRED_Presto_Player
	{

		// Plugin Domain
		public $domain = 'mycred_presto_player';
		// Plugin Slug
		public $slug = 'mycred-presto-player';

		// Instance
		protected static $_instance = NULL;

		/**
		 * Setup Instance
		 * @version 1.0
		 * 
		 */
		public static function instance()
		{
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Define
		 * @version 1.0
		 * 
		 */
		private function define($name, $value)
		{
			if (!defined($name))
				define($name, $value);
		}

		/**
		 * Require File
		 * @version 1.0
		 * 
		 */
		public function file($required_file)
		{
			if (file_exists($required_file))
				require_once $required_file;
		}

		/**
		 * Construct
		 * @version 1.0
		 * 
		 */
		public function __construct()
		{
			$this->define_constants();
			$this->init();
			$this->plugin = plugin_basename(__FILE__);

		}

		/**
		 * Initialize
		 * @version 1.0
		 * 
		 */
		private function init()
		{
			$this->file(ABSPATH . 'wp-admin/includes/plugin.php');
			if (is_plugin_active('mycred/mycred.php')) {
				$this->includes();
				add_filter('mycred_setup_hooks', array($this, 'register_hooks'), 10, 2);
				add_action('mycred_load_hooks', array($this, 'mycred_load_presto_player_hook'), 10);
				add_filter('mycred_all_references', array($this, 'register_refrences'));
				add_action('admin_enqueue_scripts', array($this, 'load_assets'));
			}
		}

		public function load_assets()
		{
			if (is_admin()) {
				wp_enqueue_script(
					'mycred-presto-player-admin',
					MYCRED_PRESTO_PLAYER_ROOT_DIR . 'assets/js/script.js',
					array('jquery'),
					'1.0',
					true
				);
			}
		}

		/**
		 * Define Constants
		 * @version 1.0
		 * 
		 */
		private function define_constants()
		{

			$this->define('MYCRED_PRESTO_PLAYER_SLUG', 'mycred-presto-player');
			$this->define('MYCRED_PRESTO_PLAYER', __FILE__);
			$this->define('MYCRED_PRESTO_PLAYER_ROOT_DIR', plugin_dir_url(MYCRED_PRESTO_PLAYER));
			$this->define('MYCRED_PRESTO_PLAYER_INCLUDES_DIR', plugin_dir_path(MYCRED_PRESTO_PLAYER) . 'includes/');
		}

		/**
		 * Include Plugin Files
		 * @version 1.0
		 * 
		 */
		public function includes()
		{
			// No helper functions file needed ye
		}

		/**
		 * Include Hook Files
		 * @version 1.0
		 * 
		 */
		public function mycred_load_presto_player_hook()
		{

			$this->file(MYCRED_PRESTO_PLAYER_INCLUDES_DIR . 'mycred-presto-player-hook.php');
			$this->file(MYCRED_PRESTO_PLAYER_INCLUDES_DIR . 'mycred-presto-player-percent-hook.php');
			$this->file(MYCRED_PRESTO_PLAYER_INCLUDES_DIR . 'mycred-presto-player-percent-range-hook.php');

		}

		public function register_hooks($installed)
		{
			$installed['presto_player_video'] = array(
				'title' => __('Presto Player', 'mycred-toolkit'),
				'description' => __('Awards %_plural% for watching videos via Presto Player.', 'mycred-toolkit'),
				'callback' => array('myCRED_Presto_Player_Hook')
			);

			$installed['presto_player_video_percent'] = array(
				'title' => __('Presto Player - Video Percent', 'mycred-toolkit'),
				'description' => __('Awards %_plural% when user watches a percentage of a video.', 'mycred-toolkit'),
				'callback' => array('myCRED_Presto_Player_Percent_Hook')
			);

			$installed['presto_player_video_percent_range'] = array(
				'title' => __('Presto Player - Video Percent Range', 'mycred-toolkit'),
				'description' => __('Awards %_plural% when user watches a video within a percentage range.', 'mycred-toolkit'),
				'callback' => array('myCRED_Presto_Player_Percent_Range_Hook')
			);

			return $installed;
		}

		public function register_refrences($list)
		{
			$list['presto_player_video'] = __('Watching a video via Presto Player', 'mycred-toolkit');
			$list['presto_player_video_percent'] = __('Watching video percent via Presto Player', 'mycred-toolkit');
			$list['presto_player_video_percent_range'] = __('Watching video percent range via Presto Player', 'mycred-toolkit');
			return $list;
		}

	}
endif;

function myCRED_presto_player()
{
	return myCRED_Presto_Player::instance();
}
myCRED_presto_player();
