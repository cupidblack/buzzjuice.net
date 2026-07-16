<?php
/**
 * Admin: Menu
 *
 * @package     AffiliateWP
 * @subpackage  Admin
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

#[\AllowDynamicProperties]

/**
 * Sets up the Affiliates menu and core submenu pages in the WordPress admin.
 *
 * @since 1.0.0
 */
class Affiliate_WP_Admin_Menu {

	/**
	 * Registers any needed hook callbacks for the component.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menus' ), 10 );
		add_action( 'admin_menu', array( $this, 'change_affiliates_admin_menu_title_to_affiliatewp' ), 11 );
		add_action( 'admin_head', array( $this, 'menu_inline_styles' ) );
		add_action( 'activated_plugin', array( $this, 'activated_rotation_plugin' ), 10, 2 );
	}

	/**
	 * Change the Affiliates Menu Item to AffiliateWP.
	 *
	 * Changing the menu title to AffiliateWP using add_menu_page() also changes the
	 * value of WP_Screen->id which Addons and various places in Core rely on.
	 *
	 * This ensures that we change the menu title but keep WP_Screen->id the same
	 * as it always has until we can afford an effort to change all references to
	 * WP_Screen->id to a new one and change the menu title using the
	 * natural add_menu_page() function.
	 *
	 * @TODO Set this using `add_menu_page()` in `$this->register_menus()` and switch all
	 *       Addon and core references to old `WP_Screen->id` since it will change.
	 *
	 * @since 2.9.5.1
	 */
	public function change_affiliates_admin_menu_title_to_affiliatewp() {

		$old_menu_title = 'Affiliates';

		global $menu;

		foreach ( $menu as $menu_position => $menu_item ) {

			if ( $old_menu_title !== $menu_item[0] ) {
				continue; // Not our menu item.
			}

			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Change Affiliates to AffiliateWP using global vs add_menu_page(), see docblock.
			$menu[ $menu_position ][0] = __( 'AffiliateWP', 'affiliate-wp' );
		}
	}

	/**
	 * Registers the Affiliates admin menu and submenu pages.
	 *
	 * @since 1.0.0
	 * @since 2.9.4  Added our Affiliate Reports link to main Dashboard menu.
	 * @since 2.12.0 Added Setup screen for onboarding new installs.
	 *
	 * @TODO See todo in $this->change_affiliates_admin_menu_title_to_affiliatewp docblock.
	 */
	public function register_menus() {
		global $submenu;

		add_menu_page(
			'AffiliateWP',
			'Affiliates', // Note, the reason we still have "Affiliates" here is because of backwards compatibility with get_current_screen()->id in which changing this would change the WP_Screen->id. See $this->change_affiliates_admin_menu_title_to_affiliatewp() docblock.
			'view_affiliate_reports',
			'affiliate-wp',
			'affwp_affiliates_dashboard',
			'',
			'25.9' . crc32( 'affiliate-wp' )
		);

		$overview   = add_submenu_page( 'affiliate-wp', __( 'Overview', 'affiliate-wp' ),    __( 'Overview', 'affiliate-wp' ),              'view_affiliate_reports',   'affiliate-wp',            'affwp_affiliates_dashboard' );

		// Notifications sub-menu — registered right after Overview so it appears
		// as the second item. Uses JS to open the panel instead of navigating.
		if ( affiliate_wp()->notifications->count_active_notifications() > 0 ) {
			add_submenu_page(
				'affiliate-wp',
				esc_html__( 'Notifications', 'affiliate-wp' ),
				sprintf(
					/* translators: %s - pulsing notification indicator dot */
					esc_html__( 'Notifications %s', 'affiliate-wp' ),
					'<div class="affwp-menu-notification-indicator"></div>'
				),
				'view_affiliate_reports',
				'affiliate-wp-notifications',
				'__return_null'
			);
		}

		$affiliates = add_submenu_page( 'affiliate-wp', __( 'Affiliates', 'affiliate-wp' ),  __( 'Affiliates', 'affiliate-wp' ),            'manage_affiliates',        'affiliate-wp-affiliates', 'affwp_affiliates_admin'     );
		$referrals  = add_submenu_page( 'affiliate-wp', __( 'Referrals', 'affiliate-wp' ),   __( 'Referrals', 'affiliate-wp' ),             'manage_referrals',         'affiliate-wp-referrals',  'affwp_referrals_admin'      );
		$payouts    = add_submenu_page( 'affiliate-wp', __( 'Payouts', 'affiliate-wp' ),     __( 'Payouts', 'affiliate-wp' ),               'manage_payouts',           'affiliate-wp-payouts',    'affwp_payouts_admin'        );
		$visits     = add_submenu_page( 'affiliate-wp', __( 'Visits', 'affiliate-wp' ),      __( 'Visits', 'affiliate-wp' ),                'manage_visits',            'affiliate-wp-visits',     'affwp_visits_admin'         );
		$creatives  = add_submenu_page( 'affiliate-wp', __( 'Creatives', 'affiliate-wp' ),   __( 'Creatives', 'affiliate-wp' ),             'manage_creatives',         'affiliate-wp-creatives',  'affwp_creatives_admin'      );
		$reports    = add_submenu_page( 'affiliate-wp', __( 'Reports', 'affiliate-wp' ),     __( 'Reports', 'affiliate-wp' ),               'view_affiliate_reports',   'affiliate-wp-reports',    'affwp_reports_admin'        );
		$tools      = add_submenu_page( 'affiliate-wp', __( 'Tools', 'affiliate-wp' ),       __( 'Tools', 'affiliate-wp' ),                 'manage_affiliate_options', 'affiliate-wp-tools',      'affwp_tools_admin'          );
		$settings   = add_submenu_page( 'affiliate-wp', __( 'Settings', 'affiliate-wp' ),    __( 'Settings', 'affiliate-wp' ),              'manage_affiliate_options', 'affiliate-wp-settings',   'affwp_settings_admin'       );
		$migration  = add_submenu_page( '', __( 'AffiliateWP Migration', 'affiliate-wp' ), __( 'AffiliateWP Migration', 'affiliate-wp' ), 'manage_affiliate_options', 'affiliate-wp-migrate',    'affwp_migrate_admin'        );
		$add_ons    = add_submenu_page(
			'affiliate-wp',
			__( 'Addons', 'affiliate-wp' ),
			__( 'Addons', 'affiliate-wp' ),
			'manage_affiliate_options',
			'affiliate-wp-add-ons',
			'affwp_add_ons_admin'
		);
		// Rotating submenu — shows one promotional page at a time.
		$rotation = $this->get_rotating_submenu();

		if ( $rotation ) {
			add_submenu_page(
				'affiliate-wp',
				$rotation['page_title'],
				$rotation['menu_title'],
				'view_affiliate_reports',
				$rotation['menu_slug'],
				$rotation['callback']
			);
		}

		// Register non-active rotation pages as hidden (accessible via direct URL).
		foreach ( $this->get_rotation_items() as $item ) {
			if ( $rotation && $item['menu_slug'] === $rotation['menu_slug'] ) {
				continue; // Already registered above.
			}

			add_submenu_page(
				'',
				$item['label'],
				$item['label'],
				'view_affiliate_reports',
				$item['menu_slug'],
				$item['callback']
			);
		}

		$about      = add_submenu_page( 'affiliate-wp', __( 'About Us', 'affiliate-wp' ),    __( 'About Us', 'affiliate-wp' ),              'view_affiliate_reports',   'affiliate-wp-about',      [ 'Affwp\Admin\About', 'display' ] );

		// Add our reports link in the main Dashboard menu.
		$submenu['index.php'][] = array(
			__( 'Affiliate Reports', 'affiliate-wp' ),
			'view_affiliate_reports',
			'admin.php?page=affiliate-wp-reports',
		);

		// Only new installs see the setup screen (until it's dismissed.)
		if ( get_option( 'affwp_display_setup_screen' ) ) {
			add_submenu_page( 'affiliate-wp', __( 'Setup', 'affiliate-wp' ), __( 'Setup', 'affiliate-wp' ), 'manage_affiliate_options', 'affiliate-wp-setup-screen', [ 'AffWP\Components\Wizard\Setup_Screen', 'display' ], 0 );
		}

		add_action( 'load-' . $affiliates, 'affwp_affiliates_screen_options' );
		add_action( 'load-' . $referrals, 'affwp_referrals_screen_options' );
		add_action( 'load-' . $payouts, 'affwp_payouts_screen_options' );
		add_action( 'load-' . $visits, 'affwp_visits_screen_options' );
		add_action( 'load-' . $creatives, 'affwp_creatives_screen_options' );
	}

	/**
	 * Output inline CSS for admin menu styling.
	 *
	 * @since 2.30.3
	 * @since 2.32.0 Added notifications menu highlight fix.
	 */
	public function menu_inline_styles() {
		echo '<style>#adminmenu .affwp-menu-new-indicator { color: #f18200; vertical-align: super; font-size: 9px; font-weight: 600; }</style>';
	}

	/**
	 * Number of days each rotation item is shown before advancing.
	 *
	 * @since 2.32.0
	 *
	 * @var int
	 */
	const ROTATION_INTERVAL_DAYS = 14;

	/**
	 * Number of days the "NEW" badge is shown when an item first appears.
	 *
	 * @since 2.32.0
	 *
	 * @var int
	 */
	const ROTATION_NEW_BADGE_DAYS = 2;

	/**
	 * Determine which submenu item to show in the rotation slot.
	 *
	 * Time-based rotation: each item shows for ROTATION_INTERVAL_DAYS days,
	 * then advances to the next. Items whose plugin is already installed are
	 * skipped. A "NEW" badge is shown for the first ROTATION_NEW_BADGE_DAYS
	 * days when an item appears for the first time.
	 *
	 * @since 2.32.0
	 *
	 * @return array|null { menu_title, page_title, menu_slug, callback } or null.
	 */
	private function get_rotating_submenu() {

		$items     = $this->get_rotation_items();
		$available = array_filter(
			$items,
			function ( $item ) {
				return ! $this->is_rotation_plugin_active( $item['plugin_files'] );
			}
		);

		// Nothing to promote — all plugins installed.
		if ( empty( $available ) ) {
			return null;
		}

		// Re-index so we can do numeric position math.
		$available = array_values( $available );
		$slugs     = wp_list_pluck( $available, 'menu_slug' );

		$state    = get_option( 'affwp_submenu_promo_state', array() );
		$now      = time();
		$current  = isset( $state['current'] ) ? (string) $state['current'] : '';
		$started  = isset( $state['started'] ) ? (int) $state['started'] : 0;
		$seen     = isset( $state['seen'] ) ? (array) $state['seen'] : array();
		$interval = self::ROTATION_INTERVAL_DAYS * DAY_IN_SECONDS;
		$advance  = false;

		// Advance if the interval has elapsed or the current item was installed.
		if ( ! empty( $current ) && ! in_array( $current, $slugs, true ) ) {
			$advance = true; // Current item's plugin was installed — skip it.
		} elseif ( $started > 0 && ( $now - $started ) >= $interval ) {
			$advance = true; // Time's up.
		}

		if ( $advance || empty( $current ) ) {
			// Find the next item after the current one.
			$pos = array_search( $current, $slugs, true );

			if ( false === $pos || $pos + 1 >= count( $available ) ) {
				$pos = 0; // Wrap around or start fresh.
			} else {
				$pos = $pos + 1;
			}

			$current = $slugs[ $pos ];
			$started = $now;
		}

		// Look up the full item.
		$item_index = array_search( $current, $slugs, true );
		$item       = $available[ $item_index ];

		// Determine if this item should get a "NEW" badge.
		$is_new   = ! in_array( $current, $seen, true );
		$in_badge = $is_new && ( $now - $started ) < ( self::ROTATION_NEW_BADGE_DAYS * DAY_IN_SECONDS );

		// Mark as seen only after the badge window closes so the badge persists.
		if ( $is_new && ! $in_badge ) {
			$seen[] = $current;
		}

		// Persist state (activated timestamps are merged in by activated_rotation_plugin).
		$new_state = array(
			'current'   => $current,
			'started'   => $started,
			'seen'      => $seen,
			'activated' => isset( $state['activated'] ) ? $state['activated'] : array(),
		);

		if ( $new_state !== $state ) {
			update_option( 'affwp_submenu_promo_state', $new_state, true );
		}

		$label = $item['label'];

		if ( $in_badge ) {
			$label .= ' <span class="affwp-menu-new-indicator">' . esc_html__( 'NEW', 'affiliate-wp' ) . '</span>';
		}

		return array(
			'menu_title' => $label,
			'page_title' => $item['label'],
			'menu_slug'  => $item['menu_slug'],
			'callback'   => $item['callback'],
		);
	}

	/**
	 * Check if any variant (lite or pro) of a rotation plugin is active.
	 *
	 * @since 2.32.0
	 *
	 * @param array $plugin_files Plugin files relative to plugins directory.
	 * @return bool
	 */
	private function is_rotation_plugin_active( $plugin_files ) {

		foreach ( $plugin_files as $file ) {
			if ( is_plugin_active( $file ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Ordered list of rotation items.
	 *
	 * @since 2.32.0
	 *
	 * @return array
	 */
	private function get_rotation_items() {

		return array(
			array(
				'label'        => __( 'Rewards', 'affiliate-wp' ),
				'menu_slug'    => 'affiliate-wp-rewards',
				'slug'         => 'rewardswp',
				'plugin_files' => array(
					'rewardswp/rewardswp.php',
					'rewardswp-pro/rewardswp-pro.php',
				),
				'callback'     => array( 'Affwp\Admin\Pages\Rewards', 'display' ),
			),
			array(
				'label'        => __( 'Cookie Consent', 'affiliate-wp' ),
				'menu_slug'    => 'affiliate-wp-cookie-consent',
				'slug'         => 'wpconsent',
				'plugin_files' => array(
					'wpconsent-cookies-banner-privacy-suite/wpconsent.php',
					'wpconsent-premium/wpconsent-premium.php',
				),
				'callback'     => array( 'Affwp\Admin\Pages\Cookie_Consent', 'display' ),
			),
			array(
				'label'        => __( 'SMTP', 'affiliate-wp' ),
				'menu_slug'    => 'affiliate-wp-smtp',
				'slug'         => 'wp-mail-smtp',
				'plugin_files' => array(
					'wp-mail-smtp/wp_mail_smtp.php',
					'wp-mail-smtp-pro/wp_mail_smtp.php',
				),
				'callback'     => array( 'Affwp\Admin\Pages\SMTP', 'display' ),
			),
		);
	}

	/**
	 * Map of rotation plugin files (lite + pro) to slugs.
	 *
	 * @since 2.32.0
	 *
	 * @return array
	 */
	private function get_rotation_plugins() {

		return array(
			'rewardswp/rewardswp.php'                                => 'rewardswp',
			'rewardswp-pro/rewardswp-pro.php'                        => 'rewardswp',
			'wpconsent-cookies-banner-privacy-suite/wpconsent.php'   => 'wpconsent',
			'wpconsent-premium/wpconsent-premium.php'                => 'wpconsent',
			'wp-mail-smtp/wp_mail_smtp.php'                          => 'wp-mail-smtp',
			'wp-mail-smtp-pro/wp_mail_smtp.php'                      => 'wp-mail-smtp',
		);
	}

	/**
	 * Record the activation time of a rotation plugin.
	 *
	 * @since 2.32.0
	 *
	 * @param string $plugin       Plugin file relative to plugins directory.
	 * @param bool   $network_wide Whether being activated network wide.
	 */
	public function activated_rotation_plugin( $plugin, $network_wide ) {

		$rotation_plugins = $this->get_rotation_plugins();
		$plugin_key       = isset( $rotation_plugins[ $plugin ] ) ? $rotation_plugins[ $plugin ] : '';

		if ( empty( $plugin_key ) ) {
			return;
		}

		$state     = get_option( 'affwp_submenu_promo_state', array() );
		$activated = isset( $state['activated'] ) ? (array) $state['activated'] : array();

		// Skip if already recorded.
		if ( isset( $activated[ $plugin_key ] ) ) {
			return;
		}

		$activated[ $plugin_key ] = time();
		$state['activated']       = $activated;

		update_option( 'affwp_submenu_promo_state', $state, true );
	}

}

$affiliatewp_menu = new Affiliate_WP_Admin_Menu();
