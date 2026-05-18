<?php

class MyCred_Wooplus_Settings {

	public static function init() {

		add_filter( 'woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50 );
		add_action( 'woocommerce_settings_tabs_settings_wooplus', __CLASS__ . '::settings_tab' );
		add_action( 'woocommerce_update_options_settings_wooplus', __CLASS__ . '::update_settings' );
		add_action( 'woocommerce_sections_settings_wooplus', __CLASS__ . '::get_sections' );
		add_action( 'admin_enqueue_scripts', __CLASS__ . '::add_js_for_rewards_points_tab' );
		add_action( 'woocommerce_admin_field_wooplus_custom_text', __CLASS__ . '::wooplus_text_html' );
		add_action('admin_init', __CLASS__ . '::mycred_woo_handle_upgrade' );


	}
	
	public static function mycred_woo_handle_upgrade() {

		$version_check = get_option('mycred_woocommerce_version');

		if (isset($_GET['action']) && $_GET['action'] == 'mycred-woo-update') {
			
			if ($version_check !== '2.0') {
				
				self::mycred_woo_run_upgrade();

				update_option('mycred_woocommerce_version', '2.0');

				wp_redirect(admin_url('admin.php?page=mycred-woocommerce'));
				exit;
			}
		}
	}


	public static function mycred_woo_run_upgrade() {
		$mycred_pref_woo = array();
		$mycred_wooplus_show_ranks = !empty(get_option('mycred_wooplus_show_ranks')) ? get_option('mycred_wooplus_show_ranks') : '';
		$mycred_partial_payment_switch = !empty(get_option('mycred_partial_payment_switch')) ? get_option('mycred_partial_payment_switch') : '';
		$mycred_wooplus_show_badges = !empty(get_option('mycred_wooplus_show_badges')) ? get_option('mycred_wooplus_show_badges') : '';
		$wooplus_ristrict_product = !empty(get_option('wooplus_ristrict_product')) ? get_option('wooplus_ristrict_product') : '';
		$wooplus_points_history = !empty(get_option('wooplus_points_history')) ? get_option('wooplus_points_history') : '';
		$reward_single_page_product = !empty(get_option('reward_single_page_product')) ? get_option('reward_single_page_product') : '';
		$reward_checkout_product_meta = !empty(get_option('reward_checkout_product_meta')) ? get_option('reward_checkout_product_meta') : '';
		$reward_checkout_product_total = !empty(get_option('reward_checkout_product_total')) ? get_option('reward_checkout_product_total') : '';
		$reward_cart_product_meta = !empty(get_option('reward_cart_product_meta')) ? get_option('reward_cart_product_meta') : '';
		$reward_cart_product_total = !empty(get_option('reward_cart_product_total')) ? get_option('reward_cart_product_total') : '';
		$mycred_wooplus_referral_cookie_name = !empty(get_option('mycred_wooplus_referral_cookie_name')) ? get_option('mycred_wooplus_referral_cookie_name') : '';
		$mycred_wooplus_referral_cookie_is_expire = !empty(get_option('mycred_wooplus_referral_cookie_is_expire')) ? get_option('mycred_wooplus_referral_cookie_is_expire') : '';
		$mycred_wooplus_referral_cookie_expiration = !empty(get_option('mycred_wooplus_referral_cookie_expiration')) ? get_option('mycred_wooplus_referral_cookie_expiration') : '';
		$mycred_partial_payments_woo = !empty(get_option('mycred_partial_payments_woo')) ? get_option('mycred_partial_payments_woo') : array();
		$mycred_wooplus_tab_on = !empty(get_option('mycred_wooplus_tab_on')) ? get_option('mycred_wooplus_tab_on') : '';
		$mycred_myaccount_tab_name = !empty(get_option('mycred_myaccount_tab_name')) ? get_option('mycred_myaccount_tab_name') : '';
		$mycred_wooplus_show_earned_badges = !empty(get_option('mycred_wooplus_show_earned_badges')) ? get_option('mycred_wooplus_show_earned_badges') : '';
		$mycred_wooplus_show_earned_ranks = !empty(get_option('mycred_wooplus_show_earned_ranks')) ? get_option('mycred_wooplus_show_earned_ranks') : '';
		$mycred_wooplus_show_my_balance = !empty(get_option('mycred_wooplus_show_my_balance')) ? get_option('mycred_wooplus_show_my_balance') : '';
		$mycred_wooplus_show_my_level = !empty(get_option('mycred_wooplus_show_my_level')) ? get_option('mycred_wooplus_show_my_level') : '';


		$mycred_pref_woo['mwp_my_account']['enable'] = ($mycred_wooplus_tab_on === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_my_account']['tab_label'] = $mycred_myaccount_tab_name;
		$mycred_pref_woo['mwp_my_account']['badges'] = ($mycred_wooplus_show_earned_badges === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_my_account']['ranks'] = ($mycred_wooplus_show_earned_ranks === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_my_account']['balances'] = ($mycred_wooplus_show_my_balance === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_my_account']['level'] = ($mycred_wooplus_show_my_level === 'yes') ? 1 : 0;
		
		$mycred_pref_woo['mwp_product_referral']['ref_cookie_name'] = $mycred_wooplus_referral_cookie_name;
		$mycred_pref_woo['mwp_product_referral']['cookie_expiration_days'] = $mycred_wooplus_referral_cookie_expiration;
		$mycred_pref_woo['mwp_product_referral']['can_cookie_expire'] = ($mycred_wooplus_referral_cookie_is_expire === 'yes') ? 1 : 0;

		$mycred_pref_woo['mwp_coupons']['module_enable'] = $mycred_partial_payment_switch == 'enable_coupons' ? 1 : 0  ;
		$mycred_pref_woo['mwp_coupons']['badge'] = ($mycred_wooplus_show_badges === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_coupons']['rank'] = ($mycred_wooplus_show_ranks === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_coupons']['point_type'] = !empty($mycred_partial_payments_woo['point_type']) ? $mycred_partial_payments_woo['point_type'] : '';
		$mycred_pref_woo['mwp_coupons']['min'] = !empty($mycred_partial_payments_woo['min']) ? $mycred_partial_payments_woo['min'] : '';
		$mycred_pref_woo['mwp_coupons']['coupon_max'] = !empty($mycred_partial_payments_woo['coupon_max']) ? $mycred_partial_payments_woo['coupon_max'] : '';
		$mycred_pref_woo['mwp_coupons']['coupon_title'] = !empty($mycred_partial_payments_woo['coupon_title']) ? $mycred_partial_payments_woo['coupon_title'] : '';
		$mycred_pref_woo['mwp_coupons']['coupon_desc'] = !empty($mycred_partial_payments_woo['coupon_desc']) ? $mycred_partial_payments_woo['coupon_desc'] : '';
		$mycred_pref_woo['mwp_coupons']['coupon_button'] = !empty($mycred_partial_payments_woo['coupon_button']) ? $mycred_partial_payments_woo['coupon_button'] : '';
		
		$mycred_pref_woo['mwp_restrict_products']['enable'] = ($wooplus_ristrict_product === 'yes') ? 1 : 0;
		
		$mycred_pref_woo['mwp_points_history']['point_history']['types']['mycred_default'] = ($wooplus_points_history === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['single_product'] = ($reward_single_page_product === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['checkout_product_meta'] = ($reward_checkout_product_meta === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['checkout_product_total'] = ($reward_checkout_product_total === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['cart_product_meta'] = ($reward_cart_product_meta === 'yes') ? 1 : 0;
		$mycred_pref_woo['mwp_display_reward']['cart_product_total'] = ($reward_cart_product_total === 'yes') ? 1 : 0;

		$mycred_pref_woo['mwp_partial_payments']['enable'] = $mycred_partial_payment_switch === 'enable_partial_payment' ? 1 : 0  ;
		$mycred_pref_woo['mwp_partial_payments']['change_position'] = !empty($mycred_partial_payments_woo['change_position']) ? $mycred_partial_payments_woo['change_position'] : 'cart';
		$mycred_pref_woo['mwp_partial_payments']['position'] = !empty($mycred_partial_payments_woo['position']) ? $mycred_partial_payments_woo['position'] : 'after';
		$mycred_pref_woo['mwp_partial_payments']['multiple'] = !empty($mycred_partial_payments_woo['multiple']) ? $mycred_partial_payments_woo['multiple'] : 'no';
		$mycred_pref_woo['mwp_partial_payments']['undo'] = !empty($mycred_partial_payments_woo['undo']) ? $mycred_partial_payments_woo['undo'] : 'no' ;
		$mycred_pref_woo['mwp_partial_payments']['sale_items'] = !empty($mycred_partial_payments_woo['sale_items']) ? $mycred_partial_payments_woo['sale_items'] : 'no' ;
		$mycred_pref_woo['mwp_partial_payments']['title'] = !empty($mycred_partial_payments_woo['title']) ? $mycred_partial_payments_woo['title'] : 'Partial Payment' ;
		$mycred_pref_woo['mwp_partial_payments']['button'] = !empty($mycred_partial_payments_woo['button']) ? $mycred_partial_payments_woo['button'] : 'Apply Discount' ;
		$mycred_pref_woo['mwp_partial_payments']['step'] = !empty($mycred_partial_payments_woo['step']) ? $mycred_partial_payments_woo['step'] : 1 ;
		$mycred_pref_woo['mwp_partial_payments']['free_shipping'] = !empty($mycred_partial_payments_woo['free_shipping']) ? $mycred_partial_payments_woo['free_shipping'] : 'no' ;
		$mycred_pref_woo['mwp_partial_payments']['checkout_total'] = !empty($mycred_partial_payments_woo['checkout_total']) ? $mycred_partial_payments_woo['checkout_total'] : 'cart' ;
		$mycred_pref_woo['mwp_partial_payments']['checkout_total_label'] = !empty($mycred_partial_payments_woo['checkout_total_label']) ? $mycred_partial_payments_woo['checkout_total_label'] : 'Point Cost' ;
		$mycred_pref_woo['mwp_partial_payments']['checkout_balance'] = !empty($mycred_partial_payments_woo['checkout_balance']) ? $mycred_partial_payments_woo['checkout_balance'] : 'cart' ;
		$mycred_pref_woo['mwp_partial_payments']['checkout_balance_label'] = !empty($mycred_partial_payments_woo['checkout_balance_label']) ? $mycred_partial_payments_woo['checkout_balance_label'] : 'Your Balance' ;
		$mycred_pref_woo['mwp_partial_payments']['selecttype'] = !empty($mycred_partial_payments_woo['selecttype']) ? $mycred_partial_payments_woo['selecttype'] : 'input' ;
		$mycred_pref_woo['mwp_partial_payments']['desc'] = !empty($mycred_partial_payments_woo['desc']) ? $mycred_partial_payments_woo['desc'] : '' ;

		
		update_option('mycred_pref_woo', $mycred_pref_woo );

		$mycred_options = get_option('mycred_pref_core', []);

		// Update min, max, and exchange rate values
		$mycred_options['mwp_min'] = ! empty( $mycred_partial_payments_woo['min']) ? $mycred_partial_payments_woo['min'] : 1;
		$mycred_options['mwp_max'] = ! empty( $mycred_partial_payments_woo['max']) ? $mycred_partial_payments_woo['max'] : 100;
		$mycred_options['exchange_rate'] = isset($mycred_partial_payments_woo['exchange']) ? $mycred_partial_payments_woo['exchange'] : 1;

		// Save the updated options back to the database
		update_option('mycred_pref_core', $mycred_options);
	}

	public static function wooplus_text_html( $value ) {

		$wooplus_text_html = __( 'If you enable the partial payment feature then <b>myCred Gateway</b> add-on will disable automatically.', 'mycredpartwoo' );
		echo wp_kses_post( mycred_wcp_kses_html( '<tr><td colspan="2"><p class="text-alignment-woocommerce-plus">' . $wooplus_text_html . '</p></td></tr>' ) );

	}

	public static function add_js_for_rewards_points_tab() {

		if ( ! empty( $_GET['section'] ) && 'reward_points' == $_GET['section'] ) {

			wp_enqueue_script( 'mycred_reward_points_tab_js', plugins_url( 'assets/js/reward_points_backend.js', MYCRED_WOOPLUS_THIS ), array(), MYCRED_WOOPLUS_VERSION );

		}

	}

	public static function get_sections() {

		global $current_section ;

		if ( '' == $current_section ) {
			$current_section = 'my_coupons';
		}

		$sections = array(
			'my_coupons'              => __( 'Coupons', 'mycredpartwoo' ),
			'ristrict_products'       => __( 'Restrict Products', 'mycredpartwoo' ),
			'points_history'          => __( 'Points History', 'mycredpartwoo' ),
			'partial_payments'        => __( 'Partial Payments', 'mycredpartwoo' ),
			'reward_points'           => __( 'Display Rewards', 'mycredpartwoo' ),
			'product_referral_cookie' => __( 'Product Referral Cookie ', 'mycredpartwoo' ),
		);
		/**
		* Filter mycred_woo_setting_sections
		* 
		* @since 1.0
		**/
		$sections = apply_filters( 'mycred_woo_setting_sections', $sections );

		if ( empty( $sections ) || 1 === count( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {

			echo '<li><a href="' . esc_url(admin_url( 'admin.php?page=wc-settings&tab=settings_wooplus&section=' . sanitize_title( $id ) )) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . esc_html($label) . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';

		}

		echo '</ul><br class="clear" />';

	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Custom Fee tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Custom Fee tab.
	 */
	public static function add_settings_tab( $settings_tabs ) {

		$settings_tabs['settings_wooplus'] = __( 'myCred', 'mycredpartwoo' );

		return $settings_tabs;

	}

	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function settings_tab() {

		woocommerce_admin_fields( self::get_settings() );

	}

	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */

	public static function update_settings() {
		woocommerce_update_options( self::get_settings() );
	}

	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public static function get_settings( $section = null ) {

		global $current_section, $mycred_partial_payment;
		
		$prefs = mycred_part_woo_account_settings();

		switch ( $current_section ) {

			case 'my_coupons':
			$settings = array(
				'section_title'              => array(
					'name'     => __( 'Badges and ranks coupon settings', 'mycredpartwoo' ),
					'type'     => 'title',
					'desc'     => '',
					'id'       => 'wdvc_tab_demo_section_title',
					'desc_tip' => true,
				),
				'mycred_wooplus_show_ranks'  => array(
					'name'     => __( 'Ranks', 'mycredpartwoo' ),
					'type'     => 'checkbox',
					'desc'     => __( 'Enable this option to reward users on achieving ranks. Settings will appear in ranks edit window once enabled', 'mycredpartwoo' ),
					'id'       => 'mycred_wooplus_show_ranks',
					'desc_tip' => true,
				),
				'mycred_wooplus_show_badges' => array(
					'name'     => __( 'Badges', 'mycredpartwoo' ),
					'type'     => 'checkbox',
					'desc'     => __( 'Enable this option to reward users on achieving Badges. Settings will appear in Badge edit window once enabled', 'mycredpartwoo' ),
					'id'       => 'mycred_wooplus_show_badges',
					'desc_tip' => true,
				),
				'section_end'                => array(
					'type' => 'sectionend',
					'id'   => 'wooplus_section_end',
				),
			);
			break;

			case 'reward_points':
			$store_currency = get_option( 'woocommerce_currency' );
			$settings       = array(
				'section_title'                 => array(
					'name'     => __( 'Reward Point settings', 'mycredpartwoo' ),
					'type'     => 'title',
					'desc'     => '',
					'id'       => 'wdvc_tab_demo_section_title',
					'desc_tip' => true,
				),
				'reward_single_page_product'    => array(
					'name'     => __( 'Single Page Product', 'mycredpartwoo' ),
					'type'     => 'checkbox',
					'desc'     => esc_html__( 'Show earn points on single product page', 'mycredpartwoo' ),
					'id'       => 'reward_single_page_product',
					'desc_tip' => true,
				),
				'reward_checkout_product_meta'  => array(
					'name'     => __( 'Checkout Product Meta', 'mycredpartwoo' ),
					'type'     => 'checkbox',
					'desc'     => esc_html__( 'Show earn points on per product on checkout page', 'mycredpartwoo' ),
					'id'       => 'reward_checkout_product_meta',
					'desc_tip' => true,
				),
				'reward_checkout_product_total' => array(
					'name'     => __( 'Checkout Product Total', 'mycredpartwoo' ),
					'type'     => 'checkbox',
					'desc'     => esc_html__( 'Enable this option to show total earn points on top of the checkout page', 'mycredpartwoo' ),
					'id'       => 'reward_checkout_product_total',
					'desc_tip' => true,
				),
				'reward_cart_product_meta'      => array(
					'name'     => __( 'Cart Product Meta', 'mycredpartwoo' ),
					'type'     => 'checkbox',
					'desc'     => esc_html__( 'Show earn points on per product on cart page', 'mycredpartwoo' ),
					'id'       => 'reward_cart_product_meta',
					'desc_tip' => true,
				),
				'reward_cart_product_total'     => array(
					'name'     => __( 'Cart Product Total', 'mycredpartwoo' ),
					'type'     => 'checkbox',
					'desc'     => esc_html__( 'Enable this option to show total earn points on top of the cart page', 'mycredpartwoo' ),
					'id'       => 'reward_cart_product_total',
					'desc_tip' => true,
				),
				'section_end'                   => array(
					'type' => 'sectionend',
					'id'   => 'wooplus_section_end',
				),
				'section_title_1'               => array(
					'name'     => __( 'Global Reward Setting', 'mycredpartwoo' ),
					'type'     => 'title',
					'desc'     => '',
					'id'       => 'wdvc_tab_global_reward_setting',
					'desc_tip' => true,
				),
				'reward_points_global'          => array(
					'name'     => __( 'Reward Points on cart total', 'mycredpartwoo' ),
					'type'     => 'checkbox',
					'desc'     => esc_html__( 'Reward Points on cart total instead of single product', 'mycredpartwoo' ),
					'id'       => 'reward_points_global',
					'desc_tip' => true,
				),
				'mycred_point_type'             => array(
					'title'    => __( 'Point Type', 'mycredpartwoo' ),
					'desc'     => __( 'Select the point type a user can reward with.', 'mycredpartwoo' ),
					'id'       => 'mycred_point_type',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'type'     => 'select',
					'options'  => mycred_get_types( true ),
					'desc_tip' => false,
				),
				'global_reward_based_on'     => array(
					'title'    => __( 'Reward Based on', 'mycredpartwoo' ),
					'id'       => 'global_reward_based_on',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'type'     => 'select',
					'options'  => array(
						'subtotal' => 'Subtotal',
						'total'    => 'Total'
					),
					'desc_tip' => false,
				),
				'reward_points_global_type'     => array(
					'title'    => __( 'Reward Point In', 'mycredpartwoo' ),
					'desc'     => __( 'Select the point type in which user will reward. fixed or percentage', 'mycredpartwoo' ),
					'id'       => 'reward_points_global_type',
					'class'    => 'wc-enhanced-select',
					'css'      => 'min-width:300px;',
					'type'     => 'select',
					'default'     => 'fixed',
					'options'  => array(
						'fixed'      => 'Fixed',
						'percentage' => 'Percentage',
						'exchange'   => 'Exchange Rate',
					),
					'desc_tip' => false,
				),
				'reward_points_global_type_val' => array(
					'name'       => __( 'Reward point Value', 'mycredpartwoo' ),
					'desc'        => __( 'This value will worked as fixed or percentage depend on above value.', 'mycredpartwoo' ),
					'id'          => 'reward_points_global_type_val',
					'type'        => 'text',
					'placeholder' => __( 'Required', 'mycredpartwoo' ),
					'desc_tip'    => true,
					'class'       => ( get_option( 'reward_points_global_type' ) === 'exchange' ) ? 'hidden' : '',	
				),
				'reward_points_exchange_rate'   => array(
					'name'       => __( 'Exchange Rate', 'mycredpartwoo' ),
					/* translators: %s: How much is 1 point worth in your store currency (%s) */
					'desc'        => sprintf( __( 'How much is 1 point worth in your store currency (%s)' , 'mycredpartwoo'), $store_currency ),
					'id'          => 'reward_points_exchange_rate',
					'type'        => 'text',
					'placeholder' => __( 'Required only if you selected exchange rate', 'mycredpartwoo' ),
					'desc_tip'    => true,
					'class'       => in_array( get_option( 'reward_points_global_type' ), array( 'fixed', 'percentage' ) ) ? 'hidden' : '',

				),
				'reward_points_global_message'  => array(
					'title'       => __( 'Reward Points Message', 'mycredpartwoo' ),
					'desc'        => __( 'Custom message to show on checkout page. use {points} to replace with points and {type} to replace with point type.', 'mycredpartwoo' ),
					'id'          => 'reward_points_global_message',
					'type'        => 'textarea',
					'placeholder' => __( 'Required', 'mycredpartwoo' ),
					'desc_tip'    => true,
				),
				'section_end_1'                 => array(
					'type' => 'sectionend',
					'id'   => 'wooplus_section_end',
				),
			);
break;

case 'ristrict_products':
$settings = array(
	'section_title'                 => array(
		'name'     => __( 'Restrict Product Based on Ranks or Badges', 'mycredpartwoo' ),
		'type'     => 'title',
		'desc'     => '',
		'id'       => 'wdvc_tab_demo_section_title',
		'desc_tip' => true,
	),

	'show_product_ristrict_product' => array(
		'name'     => __( 'Enable', 'mycredpartwoo' ),
		'type'     => 'checkbox',
		'desc'     => __( 'With this option you will have new settings in single product edit window to restrict products based on either ranks or badges.',
			'mycredpartwoo'
		),
		'id'       => 'wooplus_ristrict_product',
		'desc_tip' => true,
	),
	'section_end'                   => array(
		'type' => 'sectionend',
		'id'   => 'wooplus_section_end',
	),
);
break;

case 'points_history':
$settings = array(
	'section_title'               => array(
		'name'     => __( 'Points History', 'mycredpartwoo' ),
		'type'     => 'title',
		'desc'     => '',
		'id'       => 'wdvc_tab_demo_section_title',
		'desc_tip' => true,
	),

	'show_product_points_history' => array(
		'name'     => __( 'Enable', 'mycredpartwoo' ),
		'type'     => 'checkbox',
		'desc'     => __( 'This will display points history in my account page of logged in user.', 'mycredpartwoo' ),
		'id'       => 'wooplus_points_history',
		'desc_tip' => true,
	),
	'section_end'                 => array(
		'type' => 'sectionend',
		'id'   => 'wooplus_section_end',
	),
);
break;

case 'partial_payments':
if (empty(get_option( 'mycred_partial_payment_switch' ))) {
	get_option( 'mycred_partial_payment_switch' ) == 'disable' ;
}
$settings = array(
	array(
		'title' => __( 'Partial Payments', 'mycredpartwoo' ),
		'desc'  => __( 'Partial payments allows your users to pay for parts of the order using points. The remaining amount is paid using one of your active gateways.', 'mycredpartwoo' ),
		'type'  => 'title',
		'id'    => 'partial_payment_options',
	),
	array(
		'title'    => __( 'Use Points in Store', 'mycredpartwoo' ),
		'desc'     => __( 'This option disabled the functionality of partial payment from checkout', 'mycredpartwoo' ),
		'id'       => 'mycred_partial_payment_switch',
		'default'  => 'disable',
		'type'     => 'radio',
		'options'  => array(
			'disable'                => __( 'Disabled', 'mycredpartwoo' ),
			'enable_partial_payment' => __( 'Partial Payment', 'mycredpartwoo' ),
			'enable_coupons'         => __( 'Coupons From Points', 'mycredpartwoo' ),
		),
		'autoload' => false,
		'desc_tip' => true,
	),
	array(
		'title'    => '',
		'desc'     => '',
		'id'       => 'mycred_wooplus_custom_text',
		'default'  => 'enable',
		'type'     => 'wooplus_custom_text',
		'autoload' => false,
		'desc_tip' => false,
	),
	array(
		'title'   => __( 'Select Template to Display', 'mycredpartwoo' ),
		'desc'    => '',
		'id'      => 'mycred_partial_payments_woo[change_position]',
		'default' => $mycred_partial_payment['change_position'],
		'class'   => 'disabled',
		'type'    => 'select',
		'options' => array(
			'cart'     => __( 'In Cart', 'mycredpartwoo' ),
			'checkout' => __( 'In Checkout', 'mycredpartwoo' ),
			'both'     => __( 'Both Cart and Checkout', 'mycredpartwoo' ),
		),
	),
	array(
		'title'    => __( 'Position', 'mycredpartwoo' ),
		'desc'     => __( 'This controls where the partial payment form is shown on the checkout page.', 'mycredpartwoo' ),
		'id'       => 'mycred_partial_payments_woo[position]',
		'class'    => 'partial disabled',
		'default'  => $mycred_partial_payment['position'],
		'type'     => 'radio',
		'options'  => array(
			'after'  => __( 'After Order Total', 'mycredpartwoo' ),
			'before' => __( 'Before Order Total', 'mycredpartwoo' ),
			'none'   => __( 'Disabled - I will insert the form myself using my theme template files.', 'mycredpartwoo' ),
		),
		'autoload' => false,
		'desc_tip' => true,
	),
	array(
		'title'    => __( 'Point Type', 'mycredpartwoo' ),
		'desc'     => __( 'Select the point type a user can pay with.', 'mycredpartwoo' ),
		'id'       => 'mycred_partial_payments_woo[point_type]',
		'class'    => 'wc-enhanced-select disabled',
		'css'      => 'min-width:300px;',
		'default'  => $mycred_partial_payment['point_type'],
		'type'     => 'select',
														/**
														* Filter mycred_woo_partial_point_type_admin
														* 
														* @since 1.0
														**/
														'options'  => apply_filters( 'mycred_woo_partial_point_type_admin', mycred_get_types( true ) )/*mycred_get_types( true )*/,
														'desc_tip' => false,
													),
	array(
		'title'       => __( 'Exchange Rate', 'mycredpartwoo' ),
		'desc'        => __( 'How much is 1 point worth in your store currency?', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[exchange]',
		'class'       => 'partial disabled',
		'type'        => 'text',
		'placeholder' => __( 'Required', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['exchange'],
		'desc_tip'    => false,
		'custom_attributes' => array( 
			'min' => '1' ,
			'onkeypress' => 'return /[0-9\.]/i.test(event.key)'
		),
	),
	array(
		'title'       => __( 'Minimum', 'mycredpartwoo' ),
		'desc'        => __( 'The minimum amount of points a user must pay.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[min]',
		'type'        => 'number',
		'class'       => 'disabled',
		'placeholder' => __( 'Enter Minimum Amount', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['min'],
		'desc_tip'    => false,
		'custom_attributes' => array( 
								       		 'required' => 'required' // Adding required attribute
								       		),
	),
	array(
		'title'       => __( 'Maximum', 'mycredpartwoo' ),
		'desc'        => __( 'The maximum percentage of the cart total a user can pay using points.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[max]',
		'type'        => 'number',
		'class'       => 'partial disabled',
		'placeholder' => __( 'Enter Maximum Amount', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['max'],
		'desc_tip'    => false,
		'custom_attributes' => array( 
								       	 	'required' => 'required' // Adding required attribute
								       	 ),
	),
	array(
		'title'       => __( 'Maximum', 'mycredpartwoo' ),
		'desc'        => __( 'Limit the maximum amount of the coupon.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[coupon_max]',
		'type'        => 'number',
		'class'       => 'coupon disabled',
		'placeholder' => __( 'Enter Maximum Amount', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['coupon_max'],
		'desc_tip'    => false,
		'custom_attributes' => array( 
											'required' => 'required' // Adding required attribute
										),
	),
	array(
		'title'    => __( 'Multiple Payment', 'mycredpartwoo' ),
		'desc'     => __( 'Can users, if they can afford it, make multiple payments?', 'mycredpartwoo' ),
		'id'       => 'mycred_partial_payments_woo[multiple]',
		'class'    => 'wc-enhanced-select partial disabled',
		'css'      => 'min-width:300px;',
		'default'  => $mycred_partial_payment['multiple'],
		'type'     => 'select',
		'options'  => array(
			'no'  => __( 'No', 'mycredpartwoo' ),
			'yes' => __( 'Yes', 'mycredpartwoo' ),
		),
		'desc_tip' => false,
	),
	array(
		'title'    => __( 'Allow Undo', 'mycredpartwoo' ),
		'desc'     => __( 'Can users undo a partial payment? Undoing a partial payment will refund the amount the user paid (<b> For legacy cart </b>).', 'mycredpartwoo' ),
		'id'       => 'mycred_partial_payments_woo[undo]',
		'class'    => 'wc-enhanced-select partial disabled',
		'css'      => 'min-width:300px;',
		'default'  => $mycred_partial_payment['undo'],
		'type'     => 'select',
		'options'  => array(
			'no'  => __( 'No', 'mycredpartwoo' ),
			'yes' => __( 'Yes', 'mycredpartwoo' ),
		),
		'desc_tip' => false,
	),
	array(
		'title'   => __( 'Store Rewards', 'mycredpartwoo' ),
		'desc'    => __( 'If you are rewarding your users with points for store purchases, how do you want to handle partial point payments?', 'mycredpartwoo' ),
		'id'      => 'mycred_partial_payments_woo[rewards]',
		'class'   => 'wc-enhanced-select partial disabled',
		'css'     => 'min-width:300px;',
		'default' => $mycred_partial_payment['rewards'],
		'type'    => 'select',
		'options' => array(
			1 => __( 'I do not use store rewards', 'mycredpartwoo' ),
			2 => __( 'Deduct the partial payment amount from the amount the user gets in reward', 'mycredpartwoo' ),
			3 => __( 'Ignore partial payments and let rewards payout the appropriate amount', 'mycredpartwoo' ),
		),
	),
	array(
		'title'   => __( 'Pay for: Shipping', 'mycredpartwoo' ),
		'desc' => __( 'To grant free shipping with a coupon, select "Yes." Ensure that a free shipping method is enabled in your shipping zone and set to <br> require "A valid free shipping coupon" (see the <a href="' . admin_url('admin.php?page=wc-settings&tab=shipping') . '">Free shipping requires</a> setting).', 'mycredpartwoo' ),
		'id'      => 'mycred_partial_payments_woo[free_shipping]',
		'class'   => 'wc-enhanced-select disabled partial',
		'css'     => 'min-width:300px; display: block;',
		'default' => $mycred_partial_payment['free_shipping'],
		'type'    => 'select',
		'options' => array(
			'no'  => __( 'No', 'mycredpartwoo' ),
			'yes' => __( 'Yes', 'mycredpartwoo' ),
		),
	),
	array(
		'title'   => __( 'Pay for: Sale Items', 'mycredpartwoo' ),
		'desc'    => __( 'Can points be used to pay for items that are "on sale"?', 'mycredpartwoo' ),
		'id'      => 'mycred_partial_payments_woo[sale_items]',
		'class'   => 'wc-enhanced-select disabled',
		'css'     => 'min-width:300px;',
		'default' => $mycred_partial_payment['sale_items'],
		'type'    => 'select',
		'options' => array(
			'no'  => __( 'No', 'mycredpartwoo' ),
			'yes' => __( 'Yes', 'mycredpartwoo' ),
		),
	),
	array( 'type' => 'separator' ),
	array(
		'title'    => __( 'Selector Type', 'mycredpartwoo' ),
		'desc'     => __( 'This controls how a user indicates the amount they want to pay for the order.', 'mycredpartwoo' ),
		'id'       => 'mycred_partial_payments_woo[selecttype]',
		'class'    => 'partial disabled',
		'default'  => $mycred_partial_payment['selecttype'],
		'type'     => 'radio',
		'options'  => array(
			'input'  => __( 'Input Field - The amount needs to be typed in.', 'mycredpartwoo' ),
			'slider' => __( 'Slider - The amount changes based on the sliders position.', 'mycredpartwoo' ),
		),
		'autoload' => false,
		'desc_tip' => true,
	),
	array(
		'title'    => __( 'Step', 'mycredpartwoo' ),
		'desc'     => __( 'The point amount that each step increment.', 'mycredpartwoo' ),
		'id'       => 'mycred_partial_payments_woo[step]',
		'class'    => 'partial disabled',
		'type'     => 'text',
		'default'  => $mycred_partial_payment['step'],
		'desc_tip' => false,
	),
	array( 'type' => 'separator' ),
	array(
		'title'       => __( 'Partial Payment Title', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[title]',
		'type'        => 'text',
		'class'       => 'partial disabled',
		'placeholder' => __( 'Enter Partial Payment Title', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['title'],
		'css'         => 'min-width:300px;',
	),
	array(
		'title'       => __( 'Partial Payment Description', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[desc]',
		'class'       => 'partial disabled',
		'css'         => 'min-width:400px; width:27.5%; height: 100px;',
		'placeholder' => __( 'Enter Partial Payment Description', 'mycredpartwoo' ),
		'type'        => 'textarea',
		'default'     => $mycred_partial_payment['desc'],
		'autoload'    => false,
	),
	array(
		'title'       => __( 'Partial Payment Button Label', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[button]',
		'class'       => 'partial disabled',
		'type'        => 'text',
		'placeholder' => __( 'Enter Partial Button Label', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['button'],
		'css'         => 'min-width:300px;',
	),

	array(
		'title'       => __( 'Coupon Title', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[coupon_title]',
		'class'       => 'coupon disabled',
		'type'        => 'text',
		'placeholder' => __( 'Enter Coupon Title', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['coupon_title'],
		'css'         => 'min-width:300px;',
	),
	array(
		'title'       => __( 'Coupon Description', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[coupon_desc]',
		'class'       => 'coupon disabled',
		'css'         => 'min-width:400px; width:27.5%; height: 100px;',
		'placeholder' => __( 'Optional', 'mycredpartwoo' ),
		'type'        => 'textarea',
		'default'     => $mycred_partial_payment['coupon_desc'],
		'autoload'    => false,
	),
	array(
		'title'       => __( 'Coupon Button Label', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[coupon_button]',
		'class'       => 'coupon disabled',
		'type'        => 'text',
		'placeholder' => __( 'Enter Coupon Button Label', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['coupon_button'],
		'css'         => 'min-width:300px;',
	),
	array( 'type' => 'separator' ),
	array(
		'title'       => __( 'Log Template', 'mycredpartwoo' ),
		'desc'        => __( 'The log template used for partial payments.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[log]',
		'class'       => 'partial disabled',
		'type'        => 'text',
		'default'     => $mycred_partial_payment['log'],
		'placeholder' => __( 'Required', 'mycredpartwoo' ),
		'css'         => 'min-width:300px;',
	),
	array(
		'title'       => __( 'Refund Log Template', 'mycredpartwoo' ),
		'desc'        => __( 'The log template used for partial payment refunds. This is used when you manually remove a coupon in an order or if you allow users <br> to undo their payment before placing the order.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[log_refund]',
		'class'       => 'partial disabled',
		'type'        => 'text',
		'placeholder' => __( 'Required', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['log_refund'],
		'css'         => 'min-width:300px;',
	),
	array(
		'title'       => __( 'Refund Message', 'mycredpartwoo' ),
		'desc'        => __( 'Optional message to show to user when a partial payment was refunded.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[refund_message]',
		'class'       => 'partial disabled',
		'type'        => 'text',
		'placeholder' => __( 'Required', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['refund_message'],
		'css'         => 'min-width:300px;',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'partial_payment_options',
	),
	array(
		'title' => __( 'Checkout Totals', 'mycredpartwoo' ),
		'desc'  => __( 'Select what point related details you would like to show on the checkout page.', 'mycredpartwoo' ),
		'class' => 'disabled',
		'type'  => 'title',
		'id'    => 'partial_payment_checkout_option',
	),
	array(
		'title'   => __( 'Point Cost', 'mycredpartwoo' ),
		'desc'    => __( 'Show the cart cost in points based on our exchange rate.', 'mycredpartwoo' ),
		'id'      => 'mycred_partial_payments_woo[checkout_total]',
		'class'   => 'wc-enhanced-select disabled',
		'css'     => 'min-width:300px;',
		'default' => $mycred_partial_payment['checkout_total'],
		'type'    => 'select',
		'options' => array(
			'no'       => __( 'No', 'mycredpartwoo' ),
			'cart'     => __( 'In Cart', 'mycredpartwoo' ),
			'checkout' => __( 'In Checkout', 'mycredpartwoo' ),
			'both'     => __( 'Both Cart and Checkout', 'mycredpartwoo' ),
		),
	),
	array(
		'title'       => __( 'Total Label', 'mycredpartwoo' ),
		'desc'        => __( 'The label to use for the total cost. Supports 
			<a href="http://codex.mycred.me/category/template-tags/temp-general/" target="_blank">General Template Tags</a>', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[checkout_total_label]',
		'class'       => 'disabled',
		'type'        => 'text',
		'placeholder' => __( 'Required if enabled', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['checkout_total_label'],
		'css'         => 'min-width:300px;',
	),
	array(
		'title'   => __( 'Users Balance', 'mycredpartwoo' ),
		'desc'    => __( 'Show the current users balance.', 'mycredpartwoo' ),
		'id'      => 'mycred_partial_payments_woo[checkout_balance]',
		'class'   => 'wc-enhanced-select disabled',
		'css'     => 'min-width:300px;',
		'default' => $mycred_partial_payment['checkout_balance'],
		'type'    => 'select',
		'options' => array(
			'no'       => __( 'No', 'mycredpartwoo' ),
			'cart'     => __( 'In Cart', 'mycredpartwoo' ),
			'checkout' => __( 'In Checkout', 'mycredpartwoo' ),
			'both'     => __( 'Both Cart and Checkout', 'mycredpartwoo' ),
		),
	),
	array(
		'title'       => __( 'Balance Label', 'mycredpartwoo' ),
		'desc'        => __( 'The label to use for the balance. Supports <a href="http://codex.mycred.me/category/template-tags/temp-general/" target="_blank">General Template Tags</a>', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_woo[checkout_balance_label]',
		'class'       => 'disabled',
		'type'        => 'text',
		'placeholder' => __( 'Required if enabled', 'mycredpartwoo' ),
		'default'     => $mycred_partial_payment['checkout_balance_label'],
		'css'         => 'min-width:300px;',
	),
	array(
		'type' => 'sectionend',
		'id'   => 'partial_payment_checkout_option',
	),

													// Points History for partial payment section

	array(
		'title' => __( 'Points History', 'mycredpartwoo' ),
		'desc'  => __( 'Option to include your users points related history in the account page. When enabled or if you change the SLUG you will need to reset your permalinks.', 'mycredpartwoo' ),
		'class' => 'disabled',
		'type'  => 'title',
		'id'    => 'partial_payment_options',
	),
	array(
		'title'       => __( 'Page SLUG', 'mycredpartwoo' ),
		'desc'        => __( 'The page slug / endpoint to use. Leave empty to disable.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_account_woo[slug]',
		'class'       => 'disabled',
		'type'        => 'text',
		'placeholder' => __( 'Leave empty to disable', 'mycredpartwoo' ),
		'default'     => $prefs['slug'],
		'desc_tip'    => false,
	),
	array(
		'title'       => __( 'Page Title', 'mycredpartwoo' ),
		'desc'        => __( 'The page title.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_account_woo[title]',
		'class'       => 'disabled',
		'type'        => 'text',
		'placeholder' => __( 'Required', 'mycredpartwoo' ),
		'default'     => $prefs['title'],
		'desc_tip'    => false,
	),
	array(
		'title'       => __( 'Description', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_account_woo[desc]',
		'css'         => 'min-width:400px; width:27.5%; height: 100px;',
		'placeholder' => __( 'Optional', 'mycredpartwoo' ),
		'class'       => 'disabled',
		'type'        => 'textarea',
		'default'     => $prefs['desc'],
		'autoload'    => false,
	),
	array( 'type' => 'separator' ),
	array(
		'title'       => __( 'Number', 'mycredpartwoo' ),
		'desc'        => __( 'The number of log entries to show per page.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_account_woo[number]',
		'class'       => 'disabled',
		'type'        => 'text',
		'placeholder' => __( 'Required', 'mycredpartwoo' ),
										//'default'     => $prefs['number'],
		'value'		  => empty( $prefs['number'] ) ? 10 : $prefs['number'] ,
		'desc_tip'    => false,
	),
	array(
		'title'       => __( 'References', 'mycredpartwoo' ),
		'desc'        => __( 'Option to filter log entries to only show certain references. Leave empty to show all log entries.
			This can be either a single reference or <br> a comma separated list of references. If you want to
			show <strong>partial payments</strong> and <strong>partial payments refund</strong> logs, use "<b>store</b>" keyword.', 'mycredpartwoo' ),
		'id'          => 'mycred_partial_payments_account_woo[show]',
		'class'       => 'disabled',
		'type'        => 'text',
		'placeholder' => __( 'Optional', 'mycredpartwoo' ),
		'default'     => $prefs['show'],
		'desc_tip'    => false,
	),
	array(
		'title'    => __( 'Navigation', 'mycredpartwoo' ),
		'desc'     => __( 'Show navigation?', 'mycredpartwoo' ),
		'id'       => 'mycred_partial_payments_account_woo[nav]',
		'class'    => 'wc-enhanced-select disabled',
		'css'      => 'min-width:300px;',
		'default'  => $prefs['nav'],
		'type'     => 'select',
		'options'  => array(
			0 => __( 'No', 'mycredpartwoo' ),
			1 => __( 'Yes', 'mycredpartwoo' ),
		),
		'desc_tip' => false,
	),
	array(
		'title'    => __( 'Point Type', 'mycredpartwoo' ),
		'desc'     => __( 'Select the point type to show.', 'mycredpartwoo' ),
		'id'       => 'mycred_partial_payments_account_woo[point_type]',
		'class'    => 'wc-enhanced-select disabled',
		'css'      => 'min-width:300px;',
		'default'  => $prefs['point_type'],
		'type'     => 'select',
		'options'  => mycred_get_types( true ),
		'desc_tip' => false,
	),
	array(
		'type' => 'sectionend',
		'id'   => 'partial_payment_options',
	),
);
break;

case 'product_referral_cookie':
$settings = array(
	'section_title'=> array(
		'name'     => __( 'Product Referral Cookie', 'mycredpartwoo' ),
		'type'     => 'title',
		'desc'     => '',
		'id'       => 'mycred_wooplus_referral_cookie_title',
		'desc_tip' => false,
	),
	'mycred_wooplus_referral_cookie_name' => array(
		'name'     => __( 'Referral Cookie name', 'mycredpartwoo' ),
		'type'     => 'text',
														// 'desc'     => '',
		'id'       => 'mycred_wooplus_referral_cookie_name',
		'desc_tip' => false,
		'default'  => 'mycred_woo_product_ref',
		'desc'     => __( 'value of mycred point type will be added to cookie name', 'mycredpartwoo' ),
	),
	'mycred_wooplus_referral_cookie_is_expire' => array(
		'name'     => __( 'make cookie expire', 'mycredpartwoo' ),
		'type'     => 'checkbox',
		'id' => 'mycred_wooplus_referral_cookie_is_expire',
		'desc_tip' => false,
		'desc'     => __( 'check this option if you want to make referral cookie expire', 'mycredpartwoo' ),
	),
	'mycred_wooplus_referral_cookie_expiration' => array(
		'name' => __( 'Cookie Expiration in days', 'mycredpartwoo' ),
		'type' => 'number',
		'desc' => '',
		'id'   => 'mycred_wooplus_referral_cookie_expiration',
		'min' => 1,
	),
	'section_end'                   => array(
		'type' => 'sectionend',
		'id'   => 'mycred_wooplus_referral_cookie_title_end',
	),
);
break;

default:
$settings = array(
	'section_title'              => array(
		'name'     => __( 'Badges and ranks coupon settings', 'mycredpartwoo' ),
		'type'     => 'title',
		'desc'     => '',
		'id'       => 'wdvc_tab_demo_section_title',
		'desc_tip' => false,
	),
	'mycred_wooplus_show_ranks'  => array(
		'name'     => __( 'Ranks', 'mycredpartwoo' ),
		'type'     => 'checkbox',
		'desc'     => __( 'Enable this option to reward users on achieving ranks. Settings will appear in ranks edit window once enabled', 'mycredpartwoo' ),
		'id'       => 'mycred_wooplus_show_ranks',
		'desc_tip' => true,
	),
	'mycred_wooplus_show_badges' => array(
		'name'     => __( 'Badges', 'mycredpartwoo' ),
		'type'     => 'checkbox',
		'desc'     => __( 'Enable this option to reward users on achieving Badges. Settings will appear in Badge edit window once enabled', 'mycredpartwoo' ),
		'id'       => 'mycred_wooplus_show_badges',
		'desc_tip' => true,
	),
	'section_end'                => array(
		'type' => 'sectionend',
		'id'   => 'wooplus_section_end',
	),
);
}	

		/**
		* Filter wc_settings_wdvc_settings
		* 
		* @since 1.0
		**/
		return apply_filters( 'wc_settings_wdvc_settings', $settings, $section );
	}

}
MyCred_Wooplus_Settings::init();
