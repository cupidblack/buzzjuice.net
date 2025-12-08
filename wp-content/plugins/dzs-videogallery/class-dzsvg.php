<?php


@include_once(DZSVG_PATH . 'class_parts/class-helper.php');
@include_once(DZSVG_PATH . 'inc/php/class-ajax-functions.php');
@include_once(DZSVG_PATH . 'class_parts/class-slider-admin.php');
include(DZSVG_PATH . 'inc/php/view-functions.php');
include(DZSVG_PATH . 'inc/php/gutenberg-functions.php');
include(DZSVG_PATH . 'inc/php/ClassDzsvgHelpers.php');
include(DZSVG_PATH . 'inc/php/DzsvgAdmin.php');
include(DZSVG_PATH . 'inc/php/DzsvgPages.php');
include(DZSVG_PATH . 'inc/php/DzsvgAjax.php');
include(DZSVG_PATH . 'inc/php/DzsvgView.php');
include(DZSVG_PATH . 'inc/php/view-showcase-functions.php');
include(DZSVG_PATH . 'inc/php/analytics/debug-info.php');

if (!defined('ABSPATH')) // Or some other WordPress constant
	exit;


class DZSVideoGallery {
	public $slider_index = 0;
	public $sliders_index = 0;
	public $cats_index = 0;

	public $capability_user = 'read';
	public $capability_admin = 'manage_options';
	public $dbkey_legacyItems = 'zsvg_items';
	public $dbvpconfigsname = 'zsvg_vpconfigs';
	public $dboptionsname = 'zsvg_options';
	public $dbdcname = 'zsvg_options_dc';
	public $dbs = array();
	public $dbdbsname = 'zsvg_dbs';
	public $currDb = '';
	public $currSlider = '';
	public $sliderstructure = '';
	public $itemstructure = '';
	public $videoplayerconfig = '';
	public $mainitems;

	public $str_footer_css = '';

	public $vpsettingsdefault = array();
	public $arr_api_errors = array();

	public $mainoptions;
	public $mainoptions_dc;
	public $mainoptions_dc_aurora;
	public $mainvpconfigs;
	public $mainoptions_default;
	public $options_shortcode_generator = array();
	public $pluginmode = "plugin";
	public $alwaysembed = "on";
	public $httpprotocol = 'https';

	public $dbname_dc_aurora = 'dzsvg_options_dc';
	public $sw_content_added = false;
	public $redirect_to_intro_page = false;
	public $db_has_read_mainitems = false;
	public $view_isMultisharerOnPage = false;


	// -- deprecated
	public $isPlaylistsLegacyMode = false;

	public $analytics_views = array(); // -- video title, views, date, country
	public $analytics_minutes = array(); // -- video title, seconds, date, country
	public $analytics_users = array(); // -- user id , video title, views, seconds
	public $analytics_ip_country_db = array(); // -- ip , country

	public $plugin_justactivated = false; // -- shows if the plugin has just been activated

	public $video_player_configs = array();
	public $options_array_player = array(); // -- deprecated / used for player generator and cornerstone
	public $options_array_playlist = array();
	public $notifications = array();

	public $call_index = 50; // -- only allow 50 calls of the main shortcode function to prevent stack overflow

	public $options_item_meta = array(); // -- in options-item-meta.php
	public $options_item_meta_sanitized = array(); // -- in options-item-meta.php

	public $taxname_sliders = 'dzsvg_sliders';
	public $install_type = 'normal';
	public $script_footer_root = ''; // -- all scripts regarding video gallery init .. on root
	public $script_footer = ''; // -- all scripts regarding video gallery init

	public $classAdmin = null;
	public $classPages = null;
	public $classAjax = null;
	public $classView = null;

	/** @var DzsLayoutBuilder the DzsLayoutBuilder instance */
	public $classLayoutBuilder_menuItems = null;

	public $layout_builder = null;


	public $options_slider_categories_lng = array();
	public $item_meta_categories_lng = array();

	public $vpConfigsFrontend = array();


	public $addons_dzsvp_activated = false;

	function __construct() {

		$currDb = '';
		if (isset($_GET['dbname'])) {
			$this->currDb = sanitize_key($_GET['dbname']);
			$currDb = $this->currDb;
		}


		if (isset($_GET['currslider'])) {
			$this->currSlider = sanitize_text_field($_GET['currslider']);
		} else {
			$this->currSlider = 0;
		}


		$this->dbs = get_option($this->dbdbsname);
		if ($this->dbs == '') {
			$this->dbs = array('main');
			update_option($this->dbdbsname, $this->dbs);
		}
		if (is_array($this->dbs) && !in_array($currDb, $this->dbs) && $currDb != 'main' && $currDb != '') {
			$this->dbs[] = $currDb;
			update_option($this->dbdbsname, $this->dbs);
		}
		if ($currDb != 'main' && $currDb != '') {
			$this->dbkey_legacyItems = DZSVG_DBKEY_LEGACY_ITEMS . '-' . $currDb;
		}


		$dzsvg = $this;

		$optionsItemMeta = include(DZSVG_PATH . "configs/options-item-meta.php");
		$this->options_item_meta = $optionsItemMeta['unsanitized'];
		$this->options_item_meta_sanitized = $optionsItemMeta['sanitized'];

		$this->mainitems = get_option($this->dbkey_legacyItems);

		$this->mainvpconfigs = get_option(DZSVG_JS_VPCONFIGS_NAME_LEGACY);
		if ($this->mainvpconfigs == '' || (is_array($this->mainvpconfigs) && count($this->mainvpconfigs) == 0)) {
			$this->mainvpconfigs = array();
			$aux = file_get_contents(DZSVG_PATH . 'sampledata/sample_vpconfigs.txt');
			$this->mainvpconfigs = unserialize($aux);
			update_option(DZSVG_JS_VPCONFIGS_NAME_LEGACY, $this->mainvpconfigs);
		}


		$this->vpsettingsdefault = DZSVG_PLAYER_CONFIG_DEFAULT;


		$this->mainoptions_default = include(DZSVG_PATH . 'configs/config-default-main-options.php');

		$this->mainoptions = get_option($this->dboptionsname);

		if (isset($this->mainoptions['playlists_mode']) && $this->mainoptions['playlists_mode'] == 'legacy') {

			$this->isPlaylistsLegacyMode = true;
			if ($this->mainitems == '') {
				$mainitems_default_ser = file_get_contents(dirname(__FILE__) . '/sampledata/sample_items.txt');
				$this->mainitems = unserialize($mainitems_default_ser);
				update_option($this->dbkey_legacyItems, $this->mainitems);
			}
		}


		// -- import the main options from the configs
		if (!isset($this->mainoptions_default['enable_legacy_gutenberg_block'])) {
			$config_main_options = include(DZSVG_PATH . 'configs/config-main-options.php');
			foreach ($config_main_options as $key => $main_option) {
				$this->mainoptions_default[$key] = $main_option['default'];
			}
		}


		// --  default opts / inject into db
		if ($this->mainoptions == '') {
			$this->mainoptions_default['playlists_mode'] = 'normal';
			$this->mainoptions = $this->mainoptions_default;

			$rand = rand(0, 3);


			$this->mainoptions['youtube_api_key'] = DZSVG_YOUTUBE_SAMPLE_API_KEY_1;
			if ($rand == 1) {
				$this->mainoptions['youtube_api_key'] = DZSVG_YOUTUBE_SAMPLE_API_KEY_2;
			}
			if ($rand == 2) {
				$this->mainoptions['youtube_api_key'] = DZSVG_YOUTUBE_SAMPLE_API_KEY_3;
			}

			// -- new install
			$this->install_type = 'new';

			update_option($this->dboptionsname, $this->mainoptions);

		}

		$this->mainoptions = array_merge($this->mainoptions_default, $this->mainoptions);

		if (isset($_GET['dzsvg_debug_mode']) && $_GET['dzsvg_debug_mode'] == 'on') {
			$this->mainoptions['debug_mode'] = 'on';
		}
		// -- translation stuff
		load_plugin_textdomain(DZSVG_ID, false, basename(dirname(__FILE__)) . '/languages');


		$def_options_dc = include(DZSVG_PATH . 'configs/config-default-designer-center-options.php');
		$this->mainoptions_dc = get_option($this->dbdcname);

		// -- default opts / inject into db
		if ($this->mainoptions_dc == '') {
			$this->mainoptions_dc = $def_options_dc;
			update_option($this->dbdcname, $this->mainoptions_dc);
		}

		$def_options_dc = array('background' => '#111111', 'controls_background' => '#333333', 'scrub_background' => '#333333', 'scrub_buffer' => '#555555', 'scrub_progress' => '#fdd500', 'controls_color' => '#aaaaaa', 'controls_hover_color' => '#dddddd', 'controls_highlight_color' => '#db4343',);
		$this->mainoptions_dc_aurora = get_option($this->dbname_dc_aurora);

		// -- default opts / inject into db
		if ($this->mainoptions_dc_aurora == '') {
			$this->mainoptions_dc_aurora = array();
		}
		$this->mainoptions_dc_aurora = array_merge($def_options_dc, $this->mainoptions_dc_aurora);


		// -- init options


		if (!is_admin()) {
			if (isset($_GET['dzsvg_enabledebug']) && $_GET['dzsvg_enabledebug'] == 'on') {
				$this->mainoptions['debug_mode'] = 'on';
			}
		}


		add_action('init', array($this, 'handle_init'), 5);
		add_action('init', array($this, 'handle_init_end'), 9999);


		$this->classAdmin = new DzsvgAdmin($this);
		$this->classPages = new DzsvgPages($this);
		$this->classAjax = new DzsvgAjax($this);
		$this->classView = new DzsvgView($this);


		dzsvg_gutenberg_init();


		if (isset($this->mainoptions['enable_widget']) && $this->mainoptions['enable_widget'] == 'on' && file_exists(DZSVG_PATH . 'widget.php')) {
			include_once(DZSVG_PATH . 'widget.php');
		}


	}


	public function handle_plugin_activate() {
		$this->plugin_justactivated = "on";

		$role = get_role('administrator');

		// -- This only works, because it accesses the class instance.
		// -- would allow the author to edit others' posts for current theme only
		$role->add_cap(DZSVG_CAP_EDIT_OWN_GALLERIES);
		$role->add_cap(DZSVG_CAP_EDIT_OTHERS_GALLERIES);
		$role->add_cap('video_gallery_edit_player_configs');
		$role->add_cap('video_gallery_portal_submit_videos');


		update_option(DZSVG_DBKEY_MAINOPTIONS, $this->mainoptions);
	}

	public function handle_plugin_deactivate() {
		flush_rewrite_rules();
	}


	function check_posts_init() {
		// -- construct level


		if ($this->mainoptions['extra_css_in_stylesheet'] == 'on') {

			if (isset($_GET['dzsvg_extra_css']) && $_GET['dzsvg_extra_css'] == 'on') {


				header('HTTP/1.1 200 Ok');
				header('Content-type: text/css');
				echo $this->mainoptions['extra_css'];
				die();

			}

		}

		$this->classAjax->checkAjaxGetFunctions();

	}


	function db_read_mainitems() {


		if (!$this->db_has_read_mainitems) {

			$currDb = '';
			if (isset($_GET['dbname'])) {
				$this->currDb = sanitize_text_field($_GET['dbname']);
				$currDb = $this->currDb;
			}

			if ($this->mainoptions['playlists_mode'] == 'normal') {
				$tax = DZSVG_POST_NAME__SLIDERS;
				$terms = get_terms($tax, array(
					'hide_empty' => false,
				));
				$this->mainitems = array();
				foreach ($terms as $tm) {
					$aux = array(
						'label' => $tm->name,
						'value' => $tm->slug,
						'term_id' => $tm->term_id,
					);
					array_push($this->mainitems, $aux);
				}
			} else {
				// -- legacy
				$this->dbs = get_option($this->dbdbsname);
				if ($this->dbs == '') {
					$this->dbs = array('main');
					update_option($this->dbdbsname, $this->dbs);
				}
				if (is_array($this->dbs) && !in_array($currDb, $this->dbs) && $currDb != 'main' && $currDb != '') {
					array_push($this->dbs, $currDb);
					update_option($this->dbdbsname, $this->dbs);
				}
				if ($currDb != 'main' && $currDb != '') {
					$append_currDb_string = '-' . $currDb;
					// -- this might have been appended before ( almost certain )
					if (strpos($this->dbkey_legacyItems, $append_currDb_string) === false) {

						$this->dbkey_legacyItems .= $append_currDb_string;
					}
				}
				$this->mainitems = get_option($this->dbkey_legacyItems);
				if (is_array($this->mainitems) == false) {
					$aux = include(DZSVG_PATH . 'assets/sampledata/legacy_sliders_items_serialized.php');
					$this->mainitems = unserialize($aux);
					update_option($this->dbkey_legacyItems, $this->mainitems);
				}
			}

			$this->db_has_read_mainitems = true;
		}

	}


	function log_event($arg) {
		$fil = dirname(__FILE__) . "/log.txt";
		$fh = @fopen($fil, 'a');
		@fwrite($fh, ($arg . "\n"));
		@fclose($fh);
	}


	/**
	 * init action
	 * @return void
	 */
	function handle_init() {

		// -- init, first called
		if ($this->mainoptions['use_layout_builder_on_navigation'] == 'on') {

			if (!class_exists('DzsLayoutBuilder')) {
				include DZSVG_PATH . 'inc/layout-builder--menu-items/dzsvg-init-layout-builder.php';

				$this->classLayoutBuilder_menuItems = new DzsLayoutBuilder($this, array(
					'optionName' => DZSVG_LAYOUTBUILDER_MENU_ITEMS_OPTION_NAME,
					'pageName' => DZSVG_PAGENAME_LAYOUTBUILDER_MENU_ITEMS,
					'i18n_id' => DZSVG_ID,
					'parent_url' => DZSVG_URL,
				));
			}
		}

		if (defined('CS_ASSET_REV')) {

			if (isset($this->mainoptions['enable_cs']) && $this->mainoptions['enable_cs'] == 'on') {

				include_once DZSVG_PATH . 'inc/cornerstone/dzsvg-cornerstone.php';
				dzsvg_cornerstone_init();
			}
		}


		ClassDzsvgHelpers::legacyExportDatabaseController();
		dzsvg_view_init();
		$this->options_shortcode_generator = include_once DZSVG_PATH . 'configs/config-main-shortcode-attributes-gutenberg-playlist-block.php';

		// Register our block, and explicitly define the attributes we accept.


		dzsvg_gutenberg_register_scripts();

		include(DZSVG_PATH . "class_parts/part_sliderstructure.php");

		ClassDzsvgHelpers::facebook_maybeStartSession();

		wp_enqueue_script('jquery');
		if (is_admin()) {
			ClassDzsvgHelpers::admin_enqueueAssetsBasedOnPage();
		} else {

			// -- frontend


			if (isset($this->mainoptions['always_embed']) && $this->mainoptions['always_embed'] == 'on') {
				$this->front_scripts();
			}
		}


		ClassDzsvgHelpers::initRegisterPermalinksAndCpt();


		$this->check_posts_init();


		DzsvgAdmin::prepareVideoPlayerConfigs($this);
		// -- init options
		require_once(DZSVG_PATH . "class_parts/options_array_player.php");
		require_once(DZSVG_PATH . "class_parts/options_array_playlist.php");


		if (defined('WPB_PLUGIN_FILE')) {
			if (function_exists('vc_add_shortcode_param')) {
				vc_add_shortcode_param('dzs_add_media_att', 'vc_dzs_add_media_att');
			}
			include_once(DZSVG_PATH . 'vc/part-vcintegration.php');
		}


		if ($this->plugin_justactivated) {

			flush_rewrite_rules();
		}


		if (isset($_GET['page']) && $_GET['page'] == DZSVG_PAGENAME_LEGACY_SLIDERS && (isset($_GET['do_not_redirect']) == false || isset($_GET['do_not_redirect']) && $_GET['do_not_redirect'] != 'on')) {
			if ($this->mainoptions['playlists_mode'] == 'normal') {

				wp_redirect(admin_url('edit-tags.php?taxonomy=' . DZSVG_POST_NAME__SLIDERS . '&post_type=' . DZSVG_POST_NAME));
				exit;
			}
		}
	}

	function init_layoutBuilder($dzsvg) {
		if (!class_exists('DzsLayoutBuilder')) {
			include DZSVG_PATH . 'inc/layout-builder--menu-items/dzsvg-init-layout-builder.php';
		}
		dzsvg_init_layout_builder($dzsvg);
	}


	function handle_init_end() {

		if (is_admin()) {

			include_once(DZSVG_PATH . 'assets/admin/dzs_term_reorder.php');

			new Dzs_Term_Reorder(array(DZSVG_POST_NAME), array(DZSVG_POST_NAME => array(
				DZSVG_POST_NAME__CATEGORY
			)), array(DZSVG_POST_NAME__CATEGORY), DZSVG_URL . 'assets/admin/');
			VideoGalleryAjaxFunctions::ajax_legacy_importOrExportDatabase();
		}


	}


	function front_scripts() {
		ClassDzsvgHelpers::enqueueDzsVpPlayer();

		if ($this->mainoptions['disable_fontawesome'] != 'on') {
			wp_enqueue_style('fontawesome', DZSVG_ASSETS_URL_FONTAWESOME_CDN);
		}
	}


}

include_once(DZSVG_PATH . 'inc/embed_functions.php');
