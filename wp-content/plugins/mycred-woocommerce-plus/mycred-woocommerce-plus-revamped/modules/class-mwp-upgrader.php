<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'MWP_Upgrade_Module' ) ) :
	class MWP_Upgrade_Module extends MWP_Module {
		
		public function __construct() {
			parent::__construct( 'MWP_Upgrade_Module', array(
				'module_name' => 'mwp_upgrade',
				'add_tab'     => true,
				'title'       => __( 'Upgrade', 'mycred-woocommerce-plus'),
				'icon'        => 'dashicons-migrate',
				'tab_pos'     => 17
			) );
		}

		public function module_init() {
			add_action('admin_init', array($this, 'move_to_older_version'));
		}

		public function move_to_older_version() {
			$version_check = get_option('mycred_woocommerce_version');

		    // Check if the action is to downgrade to version 1.8
			if ( isset($_GET['action']) && $_GET['action'] == 'mycred-wooplus-move-back' ) {

				$this->mycred_woo_run_downgrade();

		        // Update the version back to 1.8
				update_option('mycred_woocommerce_version', '1.8' );
				
		        // Redirect to WooCommerce settings page or any relevant page
				wp_redirect(admin_url('admin.php?page=wc-settings&tab=settings_wooplus'));
				exit;
			}
		}

		public function mycred_woo_run_downgrade(){
			// Retrieve the settings stored in the 3.0 version
			$mycred_pref_woo = get_option('mycred_pref_woo', array());

    		// Set individual options based on the 3.0 version array
			update_option('mycred_wooplus_show_ranks', !empty($mycred_pref_woo['mwp_coupons']['rank']) ? 'yes' : 'no');
			update_option('mycred_wooplus_show_badges', !empty($mycred_pref_woo['mwp_coupons']['badge']) ? 'yes' : 'no');
			update_option('wooplus_ristrict_product', !empty($mycred_pref_woo['mwp_restrict_products']['enable']) ? 'yes' : 'no');
			update_option('wooplus_points_history', !empty($mycred_pref_woo['mwp_points_history']['point_history']['types']['mycred_default']) ? 'yes' : 'no');
			update_option('reward_single_page_product', !empty($mycred_pref_woo['mwp_display_reward']['single_product']) ? 'yes' : 'no');
			update_option('reward_checkout_product_meta', !empty($mycred_pref_woo['mwp_display_reward']['checkout_product_meta']) ? 'yes' : 'no');
			update_option('reward_checkout_product_total', !empty($mycred_pref_woo['mwp_display_reward']['checkout_product_total']) ? 'yes' : 'no');
			update_option('reward_cart_product_meta', !empty($mycred_pref_woo['mwp_display_reward']['cart_product_meta']) ? 'yes' : 'no');
			update_option('reward_cart_product_total', !empty($mycred_pref_woo['mwp_display_reward']['cart_product_total']) ? 'yes' : 'no');
			update_option('mycred_wooplus_referral_cookie_name', !empty($mycred_pref_woo['mwp_product_referral']['ref_cookie_name']) ? $mycred_pref_woo['mwp_product_referral']['ref_cookie_name'] : '');
			update_option('mycred_wooplus_referral_cookie_is_expire', !empty($mycred_pref_woo['mwp_product_referral']['can_cookie_expire']) ? 'yes' : 'no');
			update_option('mycred_wooplus_referral_cookie_expiration', !empty($mycred_pref_woo['mwp_product_referral']['cookie_expiration_days']) ? $mycred_pref_woo['mwp_product_referral']['cookie_expiration_days'] : '');
			update_option('mycred_partial_payments_woo', array(
				'point_type' => !empty($mycred_pref_woo['mwp_partial_payments']['point_type']) ? $mycred_pref_woo['mwp_partial_payments']['point_type'] : '',
				'min' => !empty($mycred_pref_woo['mwp_partial_payments']['min']) ? $mycred_pref_woo['mwp_partial_payments']['min'] : '',
				'coupon_max' => !empty($mycred_pref_woo['mwp_coupons']['coupon_max']) ? $mycred_pref_woo['mwp_coupons']['coupon_max'] : '',
				'coupon_title' => !empty($mycred_pref_woo['mwp_coupons']['coupon_title']) ? $mycred_pref_woo['mwp_coupons']['coupon_title'] : '',
				'coupon_desc' => !empty($mycred_pref_woo['mwp_coupons']['coupon_desc']) ? $mycred_pref_woo['mwp_coupons']['coupon_desc'] : '',
				'coupon_button' => !empty($mycred_pref_woo['mwp_coupons']['coupon_button']) ? $mycred_pref_woo['mwp_coupons']['coupon_button'] : '',
				'change_position' => !empty($mycred_pref_woo['mwp_partial_payments']['change_position']) ? $mycred_pref_woo['mwp_partial_payments']['change_position'] : '',
				'position' => !empty($mycred_pref_woo['mwp_partial_payments']['position']) ? $mycred_pref_woo['mwp_partial_payments']['position'] : '',
				'exchange' => !empty($mycred_pref_woo['mwp_partial_payments']['exchange']) ? $mycred_pref_woo['mwp_partial_payments']['exchange'] : '',
				'multiple' => !empty($mycred_pref_woo['mwp_partial_payments']['multiple']) ? $mycred_pref_woo['mwp_partial_payments']['multiple'] : '',
				'free_shipping' => !empty($mycred_pref_woo['mwp_partial_payments']['free_shipping']) ? $mycred_pref_woo['mwp_partial_payments']['free_shipping'] : '',
				'sale_items' => !empty($mycred_pref_woo['mwp_partial_payments']['sale_items']) ? $mycred_pref_woo['mwp_partial_payments']['sale_items'] : '',
			));
			update_option('mycred_wooplus_tab_on', !empty($mycred_pref_woo['mwp_my_account']['enable']) ? 'yes' : 'no');
			update_option('mycred_myaccount_tab_name', !empty($mycred_pref_woo['mwp_my_account']['tab_label']) ? $mycred_pref_woo['mwp_my_account']['tab_label'] : '');
			update_option('mycred_wooplus_show_earned_badges', !empty($mycred_pref_woo['mwp_my_account']['badges']) ? 'yes' : 'no');
			update_option('mycred_wooplus_show_earned_ranks', !empty($mycred_pref_woo['mwp_my_account']['ranks']) ? 'yes' : 'no');
			update_option('mycred_wooplus_show_my_balance', !empty($mycred_pref_woo['mwp_my_account']['balances']) ? 'yes' : 'no');
			update_option('mycred_wooplus_show_my_level', !empty($mycred_pref_woo['mwp_my_account']['level']) ? 'yes' : 'no');

    		// Remove the combined 3.0 settings to avoid conflicts
			delete_option('mycred_pref_woo');	
		}

		public function admin_settings( $core ) {
			$partial_payments_enabled = get_option( 'mycred_partial_payments_woo' );

			?>
			<div class="wrap">
				<?php
				?>
				<h3><?php _e( 'Move Back to Older Version', 'mycred-woocommerce-plus' ); ?></h3>
				<p><?php _e( 'You are currently using the 2.0 version. If you wish to move back to the older version, click the link below:', 'mycred-woocommerce-plus' ); ?></p>
				<?php
				echo sprintf(
					'<a href="%s">%s</a>',
					esc_url( add_query_arg( array( 'action' => 'mycred-wooplus-move-back' ), admin_url( 'admin.php?page=wc-settings&tab=settings_wooplus' ) ) ),
					__( 'Click Here to Move Back to Older Version', 'mycred-woocommerce-plus' )
				);
				
				?>
			</div>
			<?php
		}

	}
endif;

function mwp_upgrade_module() {
	$module = new MWP_Upgrade_Module();
	$module->load();
}
mwp_upgrade_module();
