<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('myCRED_WPAdverts')):
    #[AllowDynamicProperties]
    final class myCRED_WPAdverts
    {

        // Plugin Domain
        public $domain = 'mycred_wpadverts';
        // Plugin Slug
        public $slug = 'mycred-wpadverts';

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
                add_action('admin_enqueue_scripts', array($this, 'load_assets'));
                add_filter('mycred_setup_hooks', array($this, 'register_hooks'), 10, 2);
                add_action('mycred_load_hooks', array($this, 'mycred_load_wpadverts_hook'), 10);
                add_filter('mycred_all_references', array($this, 'register_references'));
            }
        }

        /**
         * Define Constants
         * @version 1.0
         * 
         */
        private function define_constants()
        {

            $this->define('MYCRED_WPADVERTS_SLUG', 'mycred-wpadverts');
            $this->define('MYCRED_WPADVERTS', __FILE__);
            $this->define('MYCRED_WPADVERTS_ROOT_DIR', plugin_dir_path(MYCRED_WPADVERTS));
            $this->define('MYCRED_WPADVERTS_INCLUDES_DIR', MYCRED_WPADVERTS_ROOT_DIR . 'includes/');
            $this->define('MYCRED_WPADVERTS_ASSETS_DIR_URL', plugin_dir_url(MYCRED_WPADVERTS) . 'assets/');
        }

        /**
         * Include Plugin Files
         * @version 1.0
         * 
         */
        public function load_assets()
        {
            if (is_admin()) {
                wp_enqueue_script(
                    'mycred-wpadverts-admin',
                    MYCRED_WPADVERTS_ASSETS_DIR_URL . 'js/script.js',
                    array('jquery'),
                    '1.0',
                    true
                );
            }
        }

        /**
         * Include Hook Files
         * @version 1.0
         * 
         */
        public function mycred_load_wpadverts_hook()
        {

            $this->file(MYCRED_WPADVERTS_INCLUDES_DIR . 'mycred-wpadverts-publish-hook.php');
            $this->file(MYCRED_WPADVERTS_INCLUDES_DIR . 'mycred-wpadverts-publish-free-hook.php');
            $this->file(MYCRED_WPADVERTS_INCLUDES_DIR . 'mycred-wpadverts-publish-paid-hook.php');
            $this->file(MYCRED_WPADVERTS_INCLUDES_DIR . 'mycred-wpadverts-contact-author-hook.php');
            $this->file(MYCRED_WPADVERTS_INCLUDES_DIR . 'mycred-wpadverts-receive-message-hook.php');
            $this->file(MYCRED_WPADVERTS_INCLUDES_DIR . 'mycred-wpadverts-renewal-hook.php');


        }

        public function register_hooks($installed)
        {
            $installed['wpadverts_publish_advert'] = array(
                'title' => __('WPAdverts - Publish Advert', 'mycred-toolkit'),
                'description' => __('Awards %_plural% for publishing adverts via WPAdverts.', 'mycred-toolkit'),
                'callback' => array('myCRED_WPAdverts_Publish_Hook')
            );

            $installed['wpadverts_publish_free_advert'] = array(
                'title' => __('WPAdverts - Publish Free Advert', 'mycred-toolkit'),
                'description' => __('Awards %_plural% for publishing free adverts (price = 0) via WPAdverts.', 'mycred-toolkit'),
                'callback' => array('myCRED_WPAdverts_Publish_Free_Hook')
            );

            $installed['wpadverts_publish_paid_advert'] = array(
                'title' => __('WPAdverts - Publish Paid Advert', 'mycred-toolkit'),
                'description' => __('Awards %_plural% for publishing paid adverts (price > 0) via WPAdverts.', 'mycred-toolkit'),
                'callback' => array('myCRED_WPAdverts_Publish_Paid_Hook')
            );

            $installed['wpadverts_contact_author'] = array(
                'title' => __('WPAdverts - Contact Author', 'mycred-toolkit'),
                'description' => __('Awards %_plural% when a user sends a message to an advert author via WPAdverts.', 'mycred-toolkit'),
                'callback' => array('myCRED_WPAdverts_Contact_Author_Hook')
            );

            $installed['wpadverts_receive_message'] = array(
                'title' => __('WPAdverts - Author Receives Message', 'mycred-toolkit'),
                'description' => __('Awards %_plural% to the advert author when they receive a message via WPAdverts.', 'mycred-toolkit'),
                'callback' => array('myCRED_WPAdverts_Receive_Message_Hook')
            );

            $installed['wpadverts_renew_advert'] = array(
                'title' => __('WPAdverts - Renew Advert', 'mycred-toolkit'),
                'description' => __('Awards %_plural% when a user renews an advert via WPAdverts.', 'mycred-toolkit'),
                'callback' => array('myCRED_WPAdverts_Renewal_Hook')
            );


            return $installed;
        }

        public function register_references($list)
        {
            $list['wpadverts_publish_advert'] = __('WPAdverts - Publish Advert', 'mycred-toolkit');
            $list['wpadverts_publish_free_advert'] = __('WPAdverts - Publish Free Advert', 'mycred-toolkit');
            $list['wpadverts_publish_paid_advert'] = __('WPAdverts - Publish Paid Advert', 'mycred-toolkit');
            $list['wpadverts_contact_author'] = __('WPAdverts - Contact Author', 'mycred-toolkit');
            $list['wpadverts_receive_message'] = __('WPAdverts - Author Receives Message', 'mycred-toolkit');
            $list['wpadverts_renew_advert'] = __('WPAdverts - Renew Advert', 'mycred-toolkit');

            return $list;
        }

    }
endif;

function myCRED_wpadverts()
{
    return myCRED_WPAdverts::instance();
}
myCRED_wpadverts();
