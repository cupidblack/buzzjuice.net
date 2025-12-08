<?php
/**
 * Plugin Name:         Give - Fee Recovery
 * Plugin URI:          https://givewp.com/addons/fee-recovery/
 * Description:         Keep more of your donations by asking donor's to take care of the fees.
 * Version:             1.9.8
 * Requires at least:   4.9
 * Requires PHP:        7.0
 * Author:              GiveWP
 * Author URI:          https://givewp.com/
 * License:             GPL-2.0+
 * License URI:         http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:         give-fee-recovery
 * Domain Path:         /languages
 */

use GiveFeeRecovery\Migrations\ServiceProvider as MigrationsServiceProvider;
use GiveFeeRecovery\ServiceProvider;
use GiveFeeRecovery\Tracking\TrackingServiceProvider;

// If this file is called directly, abort.
if ( ! defined('WPINC')) {
    die;
}
if ( ! class_exists('Give_Fee_Recovery')) :

    /**
     * Give_Fee_Recovery Class
     *
     * @package Give_Fee_Recovery
     * @since   1.2.0
     */
    final class Give_Fee_Recovery
    {

        /**
         * Holds the instance
         *
         * Ensures that only one instance of Give_Fee_Recovery exists in memory at any one
         * time and it also prevents needing to define globals all over the place.
         *
         * TL;DR This is a static property property that holds the singleton instance.
         *
         * @var object
         * @static
         */
        private static $instance;

        /**
         * Give Fee Recovery Admin Object.
         *
         * @since  1.2.0
         * @access public
         *
         * @var Give_Fee_Recovery_Admin object.
         */
        public $plugin_admin;

        /**
         * Give Fee Recovery Public Object.
         *
         * @since  1.2.0
         * @access public
         *
         * @var Give_Fee_Recovery_Public object.
         */
        public $plugin_public;

        /**
         * Notices (array)
         *
         * @since 1.7.1
         *
         * @var array
         */
        public $notices = [];

        /**
         * @since 1.9.8 add MigrationsServiceProvider
         * @since 1.9.0
         *
         * @var string[]
         */
        public $service_providers = [
            ServiceProvider::class,
            TrackingServiceProvider::class,
            MigrationsServiceProvider::class
        ];

        /**
         * Get the instance and store the class inside it. This plugin utilises
         * the PHP singleton design pattern.
         *
         * @see       Give_Fee_Recovery();
         *
         * @since     1.2.0
         * @static
         * @staticvar array $instance
         * @access    public
         *
         * @return object self::$instance Instance
         * @uses      Give_Fee_Recovery::includes() Loads all the classes.
         * @uses      Give_Fee_Recovery::licensing() Add Give Fee Recovery License.
         *
         * @uses      Give_Fee_Recovery::hooks() Setup hooks and actions.
         */
        public static function get_instance()
        {
            if ( ! isset(self::$instance) && ! (self::$instance instanceof Give_Fee_Recovery)) {
                self::$instance = new Give_Fee_Recovery();
                self::$instance->setup();
            }

            return self::$instance;
        }

        /**
         * Setup Fee Recovery.
         *
         * @since  1.3.0
         * @access private
         */
        private function setup()
        {
            self::$instance->setup_constants();

            // Activation and deactivation hooks.
            $this->init_hooks();

            add_action('before_give_init', [$this, 'register_service_providers']);
            add_action('give_init', [$this, 'init'], 10);
            add_action('admin_init', [$this, 'check_environment'], 999);
            add_action('admin_notices', [$this, 'admin_notices'], 15);
            add_action('give_loaded', [$this, 'give_loaded'], 15);
        }

        /**
         * Fire when Give class is activated.
         *
         * @since 1.7.2
         */
        public function give_loaded()
        {
            if ( ! has_action('activate_' . GIVE_PLUGIN_BASENAME, [$this, 'install'])) {
                add_action('activate_' . GIVE_PLUGIN_BASENAME, [$this, 'install'], 15);
            }
        }

        /**
         * Hook into actions and filters.
         *
         * @since  1.5.1
         */
        private function init_hooks()
        {
            register_activation_hook(GIVE_FEE_RECOVERY_PLUGIN_FILE, [$this, 'install']);
        }

        /**
         * Fee recovery install and updates.
         *
         * @access public
         * @since  1.5.1
         */
        public function install()
        {
            if ( ! $this->check_requirements()) {
                return;
            }

            // First check if Fee Recovery has been installed prior.
            $version = get_option('give_fee_recovery_version');

            // First time install, set upgrades complete
            if (empty($version)) {
                $completed_upgrades = [
                    'give_fee_recovery_v151_form_fee_earnings',
                ];

                foreach ($completed_upgrades as $completed_upgrade) {
                    give_set_upgrade_complete($completed_upgrade);
                }
            }

            // Now update version.
            $upgraded_version = ! empty($version) ? $version : GIVE_FEE_RECOVERY_VERSION;
            update_option('give_fee_recovery_version_upgraded_from', $upgraded_version);
            update_option('give_fee_recovery_version', GIVE_FEE_RECOVERY_VERSION);
        }

        /**
         * Init Fee Recovery.
         *
         * Sets up hooks, licensing and includes files.
         *
         * @since  1.0.0
         * @access public
         *
         * @return void
         */
        public function init()
        {
            if ( ! self::$instance->check_requirements()) {
                return;
            }

            self::$instance->hooks();
            self::$instance->licensing();
            self::$instance->includes();
        }

        /**
         * Check plugin environment.
         *
         * @since  1.0.0
         * @access public
         *
         * @return bool
         */
        public function check_environment()
        {
            // Flag to check whether plugin file is loaded or not.
            $is_working = true;

            // Check for if give plugin activate or not.
            $is_give_active = defined('GIVE_PLUGIN_BASENAME') ? is_plugin_active(GIVE_PLUGIN_BASENAME) : false;

            if (empty($is_give_active)) {
                // Show admin notice.
                $this->add_admin_notice(
                    'prompt_give_activate',
                    'error',
                    sprintf(
                        __(
                            '<strong>Activation Error:</strong> You must have the <a href="%s" target="_blank">Give</a> plugin installed and activated for the Fee Recovery add-on to activate.',
                            'give-fee-recovery'
                        ),
                        'https://givewp.com'
                    )
                );
                $is_working = false;
            }

            return $is_working;
        }

        /**
         * Check plugin for Give environment.
         *
         * @since  1.7.2
         * @access public
         *
         * @return bool
         */
        public function check_requirements()
        {
            // Flag to check whether plugin file is loaded or not.
            $is_working = true;

            // Verify dependency cases.
            if (defined('GIVE_VERSION')) {
                if (version_compare(GIVE_VERSION, GIVE_FEE_RECOVERY_MIN_GIVE_VER, '<')) {
                    /*
                     Min. Give. plugin version. */
                    // Show admin notice.
                    $this->add_admin_notice(
                        'prompt_give_incompatible',
                        'error',
                        sprintf(
                            __(
                                '<strong>Activation Error:</strong> You must have the <a href="%1$s" target="_blank">Give</a> core version %2$s for the Fee Recovery add-on to activate.',
                                'give-fee-recovery'
                            ),
                            'https://givewp.com',
                            GIVE_FEE_RECOVERY_MIN_GIVE_VER
                        )
                    );

                    $is_working = false;
                }
            } else {
                $is_working = false;
            }

            return $is_working;
        }

        /**
         * Setup constants.
         *
         * @since   1.0.0
         * @access  private
         */
        private function setup_constants()
        {
            if ( ! defined('GIVE_FEE_RECOVERY_VERSION')) {
                define('GIVE_FEE_RECOVERY_VERSION', '1.9.7');
            }
            if ( ! defined('GIVE_FEE_RECOVERY_MIN_GIVE_VER')) {
                define('GIVE_FEE_RECOVERY_MIN_GIVE_VER', '2.21.0');
            }
            if ( ! defined('GIVE_FEE_RECOVERY_SLUG')) {
                define('GIVE_FEE_RECOVERY_SLUG', 'give-fee-recovery');
            }
            if ( ! defined('GIVE_FEE_RECOVERY_PLUGIN_FILE')) {
                define('GIVE_FEE_RECOVERY_PLUGIN_FILE', __FILE__);
            }
            if ( ! defined('GIVE_FEE_RECOVERY_PLUGIN_DIR')) {
                define('GIVE_FEE_RECOVERY_PLUGIN_DIR', dirname(GIVE_FEE_RECOVERY_PLUGIN_FILE));
            }
            if ( ! defined('GIVE_FEE_RECOVERY_PLUGIN_URL')) {
                define('GIVE_FEE_RECOVERY_PLUGIN_URL', plugin_dir_url(GIVE_FEE_RECOVERY_PLUGIN_FILE));
            }
            if ( ! defined('GIVE_FEE_RECOVERY_BASENAME')) {
                define('GIVE_FEE_RECOVERY_BASENAME', plugin_basename(GIVE_FEE_RECOVERY_PLUGIN_FILE));
            }
        }

        /**
         * Throw error on object clone.
         *
         * The whole idea of the singleton design pattern is that there is a single
         * object therefore, we don't want the object to be cloned.
         *
         * @since  1.2.0
         * @access protected
         *
         * @return void
         */
        public function __clone()
        {
            // Cloning instances of the class is forbidden.
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'give-fee-recovery'), '1.2.0');
        }

        /**
         * Disable Unserialize of the class.
         *
         * @since  1.2.0
         * @access protected
         *
         * @return void
         */
        public function __wakeup()
        {
            // Unserialize instances of the class is forbidden.
            _doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'give-fee-recovery'), '1.2.0');
        }

        /**
         * Constructor Function.
         *
         * @since  1.0.0
         * @access protected
         */
        public function __construct()
        {
            self::$instance = $this;
        }

        /**
         * Reset the instance of the class
         *
         * @since  1.0.0
         * @access public
         */
        public static function reset()
        {
            self::$instance = null;
        }

        /**
         * Includes.
         *
         * @since  1.0.0
         * @access private
         */
        private function includes()
        {
            /**
             * The class responsible for defining all actions that occur in the admin area.
             */
            require_once GIVE_FEE_RECOVERY_PLUGIN_DIR . '/includes/admin/class-give-fee-recovery-admin.php';

            /**
             * The class responsible for defining all actions that occur in the public-facing
             * side of the site.
             */
            require_once GIVE_FEE_RECOVERY_PLUGIN_DIR . '/includes/public/class-give-fee-recovery-public.php';

            /**
             * Give Fee Recovery helper functions.
             */
            require_once GIVE_FEE_RECOVERY_PLUGIN_DIR . '/includes/class-give-fee-recovery-helper.php';

            /**
             * Give Fee recovery Upgrade.
             */
            require_once GIVE_FEE_RECOVERY_PLUGIN_DIR . '/includes/admin/upgrades/upgrade-functions.php';

            /**
             * Give Fee recovery export.
             */
            require_once GIVE_FEE_RECOVERY_PLUGIN_DIR . '/includes/admin/tools/class-give-export-donations-fee-recovery-details.php';

            self::$instance->plugin_admin = new Give_Fee_Recovery_Admin();
            self::$instance->plugin_public = new Give_Fee_Recovery_Public();
        }

        /**
         * Hooks.
         *
         * @since  1.0.0
         * @access public
         */
        public function hooks()
        {
            add_action('init', [$this, 'load_textdomain']);
            add_action('admin_init', [$this, 'activation_banner']);
            add_filter('plugin_action_links_' . GIVE_FEE_RECOVERY_BASENAME, [$this, 'action_links'], 10, 2);
            add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
        }

        /**
         * Implement Give Licensing for Give Fee Recovery Add On.
         *
         * @since  1.0.0
         * @access private
         */
        private function licensing()
        {
            if (class_exists('Give_License') && is_admin()) {
                new Give_License(
                    GIVE_FEE_RECOVERY_PLUGIN_FILE,
                    'Fee Recovery',
                    GIVE_FEE_RECOVERY_VERSION,
                    'GiveWP'
                );
            }
        }

        /**
         * Load Plugin Text Domain
         *
         * Looks for the plugin translation files in certain directories and loads
         * them to allow the plugin to be localised
         *
         * @since  1.0.0
         * @access public
         *
         * @return bool True on success, false on failure.
         */
        public function load_textdomain()
        {
            // Traditional WordPress plugin locale filter.
            $locale = apply_filters('plugin_locale', get_locale(), 'give-fee-recovery');
            $mofile = sprintf('%1$s-%2$s.mo', 'give-fee-recovery', $locale);

            // Setup paths to current locale file.
            $mofile_local = trailingslashit(plugin_dir_path(GIVE_FEE_RECOVERY_PLUGIN_FILE) . 'languages') . $mofile;

            if (file_exists($mofile_local)) {
                // Look in the /wp-content/plugins/give-fee-recovery/languages/ folder.
                load_textdomain('give-fee-recovery', $mofile_local);
            } else {
                // Load the default language files.
                load_plugin_textdomain(
                    'give-fee-recovery',
                    false,
                    trailingslashit(plugin_dir_path(GIVE_FEE_RECOVERY_PLUGIN_FILE) . 'languages')
                );
            }

            return false;
        }

        /**
         * Activation banner.
         *
         * Uses Give's core activation banners.
         *
         * @since 1.0.0
         *
         * @return bool
         */
        public function activation_banner()
        {
            // Check for activation banner inclusion.
            if (
                ! class_exists('Give_Addon_Activation_Banner')
                && file_exists(GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php')
            ) {
                include GIVE_PLUGIN_DIR . 'includes/admin/class-addon-activation-banner.php';
            }

            // Initialize activation welcome banner.
            if (class_exists('Give_Addon_Activation_Banner')) {
                // Only runs on admin.
                $args = [
                    'file' => GIVE_FEE_RECOVERY_PLUGIN_FILE,
                    'name' => __('Fee Recovery', 'give-fee-recovery'),
                    'version' => GIVE_FEE_RECOVERY_VERSION,
                    'settings_url' => admin_url('edit.php?post_type=give_forms&page=give-settings&tab=givefeerecovery'),
                    'documentation_url' => 'http://docs.givewp.com/addon-fee-recovery/',
                    'support_url' => 'https://givewp.com/support/',
                    'testing' => false,
                ];
                new Give_Addon_Activation_Banner($args);
            }

            return true;
        }

        /**
         * Adding additional setting page link along plugin's action link.
         *
         * @since   1.0.0
         * @access  public
         *
         * @param array $actions get all actions.
         *
         * @return  array       return new action array
         */
        function action_links($actions)
        {
            if ( ! class_exists('Give')) {
                return $actions;
            }

            // Check min Give version.
            if (defined('GIVE_FEE_RECOVERY_MIN_GIVE_VER') && version_compare(
                    GIVE_VERSION,
                    GIVE_FEE_RECOVERY_MIN_GIVE_VER,
                    '<'
                )) {
                return $actions;
            }

            $new_actions = [
                'settings' => sprintf(
                    '<a href="%1$s">%2$s</a>',
                    admin_url('edit.php?post_type=give_forms&page=give-settings&tab=givefeerecovery'),
                    __('Settings', 'give-fee-recovery')
                ),
            ];

            return array_merge($new_actions, $actions);
        }

        /**
         * Plugin row meta links.
         *
         * @since   1.0.0
         * @access  public
         *
         * @param array $plugin_meta An array of the plugin's metadata.
         * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
         *
         * @return  array  return meta links for plugin.
         */
        function plugin_row_meta($plugin_meta, $plugin_file)
        {
            if ( ! class_exists('Give')) {
                return $plugin_meta;
            }

            // Return if not Give Fee Recovery plugin.
            if ($plugin_file !== GIVE_FEE_RECOVERY_BASENAME) {
                return $plugin_meta;
            }

            $new_meta_links = [
                sprintf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    esc_url(
                        add_query_arg(
                            [
                                'utm_source' => 'plugins-page',
                                'utm_medium' => 'plugin-row',
                                'utm_campaign' => 'admin',
                            ],
                            'http://docs.givewp.com/addon-fee-recovery'
                        )
                    ),
                    __('Documentation', 'give-fee-recovery')
                ),
                sprintf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    esc_url(
                        add_query_arg(
                            [
                                'utm_source' => 'plugins-page',
                                'utm_medium' => 'plugin-row',
                                'utm_campaign' => 'admin',
                            ],
                            'https://givewp.com/addons/'
                        )
                    ),
                    __('Add-ons', 'give-fee-recovery')
                ),
            ];

            return array_merge($plugin_meta, $new_meta_links);
        }

        /**
         * Allow this class and other classes to add notices.
         *
         * @since 1.7.2
         *
         * @param $slug
         * @param $class
         * @param $message
         */
        public function add_admin_notice($slug, $class, $message)
        {
            $this->notices[$slug] = [
                'class' => $class,
                'message' => $message,
            ];
        }

        /**
         * Display admin notices.
         *
         * @since 1.7.2
         */
        public function admin_notices()
        {
            $allowed_tags = [
                'a' => [
                    'href' => [],
                    'title' => [],
                    'class' => [],
                    'id' => [],
                ],
                'br' => [],
                'em' => [],
                'span' => [
                    'class' => [],
                ],
                'strong' => [],
            ];

            foreach ((array)$this->notices as $notice_key => $notice) {
                echo "<div class='" . esc_attr($notice['class']) . "'><p>";
                echo wp_kses($notice['message'], $allowed_tags);
                echo '</p></div>';
            }
        }

        /**
         * Registers the Service Providers with GiveWP core
         *
         * @since 1.9.0
         */
        public function register_service_providers()
        {
            if ( ! Give_Fee_Recovery()->check_requirements()) {
                return;
            }

            foreach ($this->service_providers as $service_provider) {
                give()->registerServiceProvider($service_provider);
            }
        }
    } //End Give_Fee_Recovery Class.

endif;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Loads a single instance of Give Fee Recovery.
 *
 * This follows the PHP singleton design pattern.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @example <?php $give_fee_recovery = Give_Fee_Recovery(); ?>
 *
 * @since   1.0.0
 *
 * @see     Give_Fee_Recovery::get_instance()
 *
 * @return object Give_Fee_Recovery Returns an instance of the  class
 */
function Give_Fee_Recovery()
{
    return Give_Fee_Recovery::get_instance();
}

Give_Fee_Recovery();
