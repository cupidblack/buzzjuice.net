<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('myCRED_FluentCart')) :
    #[AllowDynamicProperties]
    final class myCRED_FluentCart
    {
        public $domain = 'mycred_fluentcart';
        public $slug   = 'mycred-fluent-cart';

        protected static $_instance = null;

        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        private function define($name, $value)
        {
            if (!defined($name)) {
                define($name, $value);
            }
        }

        public function file($required_file)
        {
            if (file_exists($required_file)) {
                require_once $required_file;
            }
        }

        public function __construct()
        {
            $this->define_constants();
            $this->init();
            $this->plugin = plugin_basename(__FILE__);
        }

        private function init()
        {
            $this->file(ABSPATH . 'wp-admin/includes/plugin.php');

            if (is_plugin_active('mycred/mycred.php') && is_plugin_active('fluent-cart/fluent-cart.php')) {
                $this->includes();
                add_action('admin_enqueue_scripts', array($this, 'load_assets'));
                add_filter('mycred_setup_hooks', array($this, 'register_hooks'), 10, 2);
                add_action('mycred_load_hooks', array($this, 'load_hook_files'), 10);
                add_filter('mycred_all_references', array($this, 'register_references'));
            }
        }

        private function define_constants()
        {
            $this->define('MYCRED_FLUENT_CART_SLUG', 'mycred-fluent-cart');
            $this->define('MYCRED_FLUENT_CART', __FILE__);
            $this->define('MYCRED_FLUENT_CART_ROOT_DIR', plugin_dir_path(MYCRED_FLUENT_CART));
            $this->define('MYCRED_FLUENT_CART_INCLUDES_DIR', MYCRED_FLUENT_CART_ROOT_DIR . 'includes/');
        }

        /**
         * Include Plugin Files
         * @version 1.0
         * 
         */
        public function includes() {
            $this->file( MYCRED_FLUENT_CART_INCLUDES_DIR . 'mycred-fluentcart-functions.php' );
        }

        public function load_hook_files()
        {
            $this->file(MYCRED_FLUENT_CART_INCLUDES_DIR . 'mycred-fluent-cart-purchase-hook.php');
            $this->file(MYCRED_FLUENT_CART_INCLUDES_DIR . 'mycred-fluent-cart-full-refund-hook.php');
            $this->file(MYCRED_FLUENT_CART_INCLUDES_DIR . 'mycred-fluent-cart-partial-refund-hook.php');
            $this->file(MYCRED_FLUENT_CART_INCLUDES_DIR . 'mycred-fluent-cart-subscription-activated-hook.php');
            $this->file(MYCRED_FLUENT_CART_INCLUDES_DIR . 'mycred-fluent-cart-subscription-cancelled-hook.php');
            $this->file(MYCRED_FLUENT_CART_INCLUDES_DIR . 'mycred-fluent-cart-subscription-renewed-hook.php');
        }

        public function load_assets()
        {
            if (is_admin() && function_exists('is_mycred_hook_page') && is_mycred_hook_page()) {
                wp_enqueue_script(
                    'mycred-fluentcart-script',
                    plugin_dir_url(MYCRED_FLUENT_CART) . 'assets/js/script.js',
                    array('jquery'),
                    '1.0.0',
                    true
                );
            }
        }

        public function register_hooks($installed)
        {
            $installed['fluentcart_product_purchase'] = array(
                'title'       => __('Product Purchase (FluentCart)', 'mycred-toolkit'),
                'description' => __('Awards myCred points when a user completes a purchase in FluentCart.', 'mycred-toolkit'),
                'callback'    => array('myCRED_FluentCart_Purchase_Hook')
            );
            $installed['fluentcart_order_full_refund'] = array(
                'title'       => __('Order Fully Refunded (FluentCart)', 'mycred-toolkit'),
                'description' => __('Awards myCred points when an order is fully refunded in FluentCart.', 'mycred-toolkit'),
                'callback'    => array('myCRED_FluentCart_Full_Refund_Hook')
            );
            $installed['fluentcart_order_partial_refund'] = array(
                'title'       => __('Order Partially Refunded (FluentCart)', 'mycred-toolkit'),
                'description' => __('Awards myCred points when an order is partially refunded in FluentCart.', 'mycred-toolkit'),
                'callback'    => array('myCRED_FluentCart_Partial_Refund_Hook')
            );
            $installed['fluentcart_subscription_activated'] = array(
                'title'       => __('Subscription Activated (FluentCart)', 'mycred-toolkit'),
                'description' => __('Awards myCred points when a subscription is activated in FluentCart, optionally for specific products.', 'mycred-toolkit'),
                'callback'    => array('myCRED_FluentCart_Subscription_Activated_Hook')
            );
            $installed['fluentcart_subscription_cancelled'] = array(
                'title'       => __('Subscription Cancelled (FluentCart)', 'mycred-toolkit'),
                'description' => __('Awards myCred points when a subscription is cancelled in FluentCart, optionally for specific products.', 'mycred-toolkit'),
                'callback'    => array('myCRED_FluentCart_Subscription_Cancelled_Hook')
            );
            $installed['fluentcart_subscription_renewed'] = array(
                'title'       => __('Subscription Renewed (FluentCart)', 'mycred-toolkit'),
                'description' => __('Awards myCred points when a subscription is renewed in FluentCart, optionally for specific products.', 'mycred-toolkit'),
                'callback'    => array('myCRED_FluentCart_Subscription_Renewed_Hook')
            );

            return $installed;
        }

        public function register_references($list)
        {
            $list['fluentcart_product_purchase'] = __('Product purchased in FluentCart', 'mycred-toolkit');
            $list['fluentcart_order_full_refund'] = __('Order fully refunded in FluentCart', 'mycred-toolkit');
            $list['fluentcart_order_partial_refund'] = __('Order partially refunded in FluentCart', 'mycred-toolkit');
            $list['fluentcart_subscription_activated'] = __('Subscription activated in FluentCart', 'mycred-toolkit');
            $list['fluentcart_subscription_cancelled'] = __('Subscription cancelled in FluentCart', 'mycred-toolkit');
            $list['fluentcart_subscription_renewed'] = __('Subscription renewed in FluentCart', 'mycred-toolkit');
            return $list;
        }
    }
endif;

function myCRED_fluentcart()
{
    return myCRED_FluentCart::instance();
}
myCRED_fluentcart();

