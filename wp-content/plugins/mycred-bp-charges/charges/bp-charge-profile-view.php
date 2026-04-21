<?php
if ( ! defined( 'BP_CHARGES_VERSION' ) ) exit;

/**
 * Charge for Profile Views
 * @since 1.0
 * @version 1.1.1
 */
if ( ! class_exists( 'myCRED_BP_Charge_View_Profile' ) ) :
	class myCRED_BP_Charge_View_Profile extends myCRED_BP_Charge {

		/**
		 * Construct
		 */
		function __construct( $charge_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'view_profile',
				'defaults' => array(
					'ctype'              => MYCRED_DEFAULT_TYPE_KEY,
					'default_expire'     => 0,
					'profile_buttons'    => 0,
					'log_buy'            => 'Profile Access to %user_profile_link%',
					'log_sell'           => 'Access purchase by %display_name%',
					'default_setup'      => 'off',
					'default_price'      => 1,
					'min_price'          => 1,
					'max_price'          => 1000,
					'fee'                => 0,
					'price'              => 1,
					'profit_share'       => 0,
					'sell_menu_title'    => 'Sell Access',
					'sell_menu_slug'     => 'sell-access',
					'sell_menu_pos'      => 75,
					'buy_menu_title'     => 'Buy Access',
					'buy_menu_slug'      => 'buy-access',
					'buy_menu_pos'       => 75,
					'sale_template'      => '<h1>Buy Access</h1>
<p class="bp-charges-message">Gain access to this profile for only <strong>%cred_f%</strong><br />
Purchases expires: <strong>%expires%</strong></p>
<p id="bp-charges-buy-button">%button%</p>
<h3>Buyers (%buyer_count%)</h3>
%buyers%',
					'cant_buy_template'  => '<h1>Buy Access</h1>
<p class="bp-charges-message">Insufficient funds.</p>
<h3>Buyers (%buyer_count%)</h3>
%buyers%',
					'non_member_template' => '<h1>Login to Buy Access</h1>
<p class="bp-charges-message">Join our community and gain access to profiles.</p>',
					'hide_activities'   => 0,
					'activity_template' => '<a href="%buyurl%">Buy access</a> to view this members activities'
				)
			), $charge_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function run() {

			if ( isset( $this->prefs['ctype'] ) && $this->prefs['ctype'] != '' )
				$this->core = mycred( $this->prefs['ctype'] );

			add_action( 'bp_init',             array( $this, 'module_init' ), 120 );
			add_action( 'template_notices',    array( $this, 'notices' ) );
			add_filter( 'template_redirect',   array( $this, 'screen_override' ), 0, 2 );

			add_action( 'bp_setup_nav',       array( $this, 'profile_link_setup' ) );
			
			add_action( 'bp_get_activity_content_body', array( $this, 'filter_activities' ), 999, 2 );

			if ( isset( $this->prefs['profile_buttons'] ) && $this->prefs['profile_buttons'] == 1 ) {
				add_filter( 'bp_get_send_public_message_button', array( $this, 'hide_public_message_button' ), 9999 );
				add_filter( 'bp_get_send_message_button_args',   array( $this, 'hide_public_message_button' ), 9999 );
				add_filter( 'bp_get_add_friend_button',          array( $this, 'hide_friendship_button' ), 9999 );
			}

			add_filter( 'mycred_all_references', array( $this, 'add_badge_support' ), 80 );

			add_action( 'mycred_overview_after', array( $this, 'overview' ), 20 );

		}

		/**
		 * Add Badge Support
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			$references['profile_access'] = __( 'Profile Access Payment (BP Charges)', 'bp_charge' );
			$references['profile_access_sale'] = __( 'Profile Access Sale (BP Charges)', 'bp_charge' );

			return $references;

		}

		/**
		 * Module Init
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function module_init() {

			if ( ! is_user_logged_in() ) return;

			// Only applicable to the user profile or any custom reason via the filter.
			if ( ! bp_is_user() ) return;

			// Purchase
			if ( isset( $_GET['buy-access-to-profile'] ) && $_GET['buy-access-to-profile'] != '' )
				$this->buy_access_to_profile();

			// Save Profile Setup
			if ( isset( $_POST['sell_access'] ) && isset( $_REQUEST['update-sell-access'] ) && wp_verify_nonce( $_REQUEST['update-sell-access'], 'update-bp-sell-access' ) )
				$this->save_profile_setup();

		}

		/**
		 * Show Overview Totals
		 * @since 1.0
		 * @version 1.0
		 */
		public function overview() {

			global $wpdb;

			$mycred     = mycred( $this->prefs['ctype'] );

			$page       = MYCRED_SLUG;
			if ( $this->prefs['ctype'] != MYCRED_DEFAULT_TYPE_KEY )
				$page .= '_' . $this->prefs['ctype'];

			$url        = admin_url( 'admin.php?page=' . $page );

			$access_url = add_query_arg( array( 'ref' => 'profile_access' ), $url );
			$access     = $wpdb->get_var( $wpdb->prepare( "SELECT SUM( creds ) FROM {$mycred->log_table} WHERE ref = %s AND ctype = %s;", 'profile_access', $this->prefs['ctype'] ) );
			$access     = ( $access === NULL ) ? 0 : abs( $access );

			$sale_url   = add_query_arg( array( 'ref' => 'profile_access_sale' ), $url );
			$sale       = $wpdb->get_var( $wpdb->prepare( "SELECT SUM( creds ) FROM {$mycred->log_table} WHERE ref = %s AND ctype = %s;", 'profile_access_sale', $this->prefs['ctype'] ) );
			$sale       = ( $sale === NULL ) ? 0 : $sale;

			$total      = $access - $sale;

?>
<div class="mycred-type clear first">
	<div class="module-title"><div class="type-icon"><div class="dashicons dashicons-admin-users"></div></div><?php _e( 'Profile Access', 'bp_charge' ); ?><a href="<?php echo $url; ?>"><?php echo $mycred->format_creds( $total ); ?></a></div>
	<div class="overview clear">
		<div class="section border" style="width: 50%;">
			<p><strong><?php _e( 'Total Payments', 'bp_charge' ); ?>:</strong> <a href="<?php echo esc_url( $access_url ); ?>"><?php echo $mycred->format_creds( $access ); ?></a></p>
		</div>
		<div class="section border" style="width: 50%; margin-left: -1px;">
			<p><strong><?php _e( 'Total Payouts', 'bp_charge' ); ?>:</strong> <a href="<?php echo esc_url( $sale_url ); ?>"><?php echo $mycred->format_creds( $sale ); ?></a></p>
		</div>
	</div>
</div>
<?php

		}

		/**
		 * Filter Activities
		 * @since 1.1
		 * @version 1.0
		 */
		public function filter_activities( $content, $activity ) {

			//return $content .= '<pre>' . print_r( $activity, true ) . '</pre>';
			//if ( ! isset( $activity->component ) || $activity->component != 'activity' ) return $content;

			$current = get_current_user_id();
			$poster  = $activity->user_id;

			if ( $current == $poster || bp_current_user_can( 'bp_moderate' ) ) return $content;

			if ( $this->profile_for_sale( $poster ) && ! $this->user_has_paid( $poster, $current ) ) {

				$content = $this->core->template_tags_general( $this->prefs['activity_template'] );
				$content = str_replace( '%buyurl%', bp_core_get_user_domain( $poster ) . $this->prefs['buy_menu_slug'] . '/', $content );

			}

			return $content;

		}

		/**
		 * BuddyPress Profile Block
		 * Redirect visitors that have not paid to the purchase page
		 * in profile.
		 * @since 1.1
		 * @version 1.0
		 */
		public function screen_override() {

			if ( ! function_exists( 'bp_is_my_profile' ) ) return;

			if ( ! bp_is_user() ) return;

			global $bp;

			if ( $bp->current_component == $this->prefs['buy_menu_slug'] ) return;

			$override = false;

			// Always override for visitors
			if ( ! is_user_logged_in() )
				$override = true;

			if ( ! $override ) {

				// Check if the current user has paid or needs to pay
				if ( ! bp_is_my_profile() && $this->profile_for_sale() && ! $this->user_has_paid() )
					$override = true;

			}

			if ( $override && $this->profile_for_sale() ) {

				global $bp;

				$url = bp_core_get_user_domain( bp_displayed_user_id() ) . $this->prefs['buy_menu_slug'] . '/';
				wp_redirect( $url );
				exit;

			}

		}

		/**
		 * Notices
		 * @since 1.0
		 * @version 1.0
		 */
		public function notices() {

			if ( ! bp_is_user() ) return;

			$current_action = bp_current_action();
			if ( isset( $_GET['saved'] ) && $_GET['saved'] == 1 && $current_action == 'setup' )
				echo '<div id="message" class="bp-template-notice updated"><p>' . __( 'Settings Saved.', 'bp_charge' ) . '</p></div>';

			elseif ( isset( $_GET['purchased'] ) && $_GET['purchased'] == 1 )
				echo '<div id="message" class="bp-template-notice updated"><p>' . __( 'Purchase Completed.', 'bp_charge' ) . '</p></div>';

		}

		/**
		 * Hide Buttons
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function hide_public_message_button( $args ) {

			if ( ! bp_is_my_profile() && $this->profile_for_sale() && ! $this->user_has_paid() ) {

				return false;

			}

			return $args;

		}

		/**
		 * Hide Buttons
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function hide_friendship_button( $args ) {

			if ( $args['id'] != 'not_friends' ) return $args;

			$user_id = str_replace( 'friend-', '', $args['link_id'] );
			if ( absint( $user_id ) === 0 ) return $args;

			$logged_in_user_id = get_current_user_id();
			if ( $user_id != $logged_in_user_id && $this->profile_for_sale( $user_id ) && ! $this->user_has_paid( $user_id, $logged_in_user_id ) ) {

				return false;

			}

			return $args;

		}

		/**
		 * Adjust Admin Bar
		 * @since 1.0
		 * @version 1.0
		 */
		public function admin_bar() {

			// Bail if this is an ajax request
			if ( defined( 'DOING_AJAX' ) )
				return;

			// Do not proceed if BP_USE_WP_ADMIN_BAR constant is not set or is false
			if ( ! bp_use_wp_admin_bar() )
				return;

			global $bp;

			$admin_menus   = array();
			$admin_menus[] = array(
				'parent' => $bp->my_account_menu_id,
				'id'     => 'my-account-' . $this->prefs['sell_menu_slug'],
				'title'  => $this->prefs['sell_menu_title'],
				'href'   => $bp->displayed_user->domain . $this->prefs['sell_menu_slug'] . '/'
			);

			// Stats
			$admin_menus[] = array(
				'parent' => 'my-account-' . $this->prefs['sell_menu_slug'],
				'id'     => 'my-account-' . $this->prefs['sell_menu_slug'] . '-stats',
				'title'  => __( 'Stats', 'bp_charge' ),
				'href'   => $bp->displayed_user->domain . $this->prefs['sell_menu_slug'] . '/'
			);

			// Settings
			if ( isset( $this->prefs['default_setup'] ) && $this->prefs['default_setup'] == 'off' )
				$admin_menus[] = array(
					'parent' => 'my-account-' . $this->prefs['sell_menu_slug'],
					'id'     => 'my-account-' . $this->prefs['sell_menu_slug'] . '-setup',
					'title'  => __( 'Settings', 'bp_charge' ),
					'href'   => $bp->displayed_user->domain . $this->prefs['sell_menu_slug'] . '/setup/'
				);

			global $wp_admin_bar;

			// Add each admin menu
			foreach( $admin_menus as $admin_menu ) {
				$wp_admin_bar->add_menu( $admin_menu );
			}

		}

		/**
		 * Profile Link Setup
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function profile_link_setup() {

			// Not applicable for excluded users
			if ( $this->core->exclude_user( bp_displayed_user_id() ) ) return;

			global $bp;

			if ( bp_is_my_profile() || mycred_is_admin() ) {

				// Root
				bp_core_new_nav_item( array(
					'name'                    => $this->prefs['sell_menu_title'],
					'slug'                    => $this->prefs['sell_menu_slug'],
					'parent_url'              => $bp->displayed_user->domain,
					'default_subnav_slug'     => $this->prefs['sell_menu_slug'],
					'screen_function'         => array( $this, 'sell_access_page' ),
					'show_for_displayed_user' => true,
					'position'                => $this->prefs['sell_menu_pos']
				) );

				// Stats
				bp_core_new_subnav_item( array(
					'name'            => __( 'Stats', 'bp_charge' ),
					'slug'            => $this->prefs['sell_menu_slug'],
					'parent_url'      => $bp->displayed_user->domain . $this->prefs['sell_menu_slug'] . '/',
					'parent_slug'     => $this->prefs['sell_menu_slug'],
					'screen_function' => array( $this, 'sell_access_page' ),
					'user_has_access' => true
				) );

				// Settings
				if ( isset( $this->prefs['default_setup'] ) && $this->prefs['default_setup'] == 'off' )
					bp_core_new_subnav_item( array(
						'name'            => __( 'Settings', 'bp_charge' ),
						'slug'            => 'setup',
						'parent_url'      => $bp->displayed_user->domain . $this->prefs['sell_menu_slug'] . '/',
						'parent_slug'     => $this->prefs['sell_menu_slug'],
						'screen_function' => array( $this, 'sell_access_setup_page' ),
						'user_has_access' => true
					) );

			}

			elseif ( $this->profile_for_sale() ) {

				// Root
				bp_core_new_nav_item( array(
					'name'                    => $this->prefs['buy_menu_title'],
					'slug'                    => $this->prefs['buy_menu_slug'],
					'parent_url'              => $bp->displayed_user->domain,
					'default_subnav_slug'     => $this->prefs['buy_menu_slug'],
					'screen_function'         => array( $this, 'buy_access_page' ),
					'show_for_displayed_user' => true,
					'position'                => $this->prefs['buy_menu_pos']
				) );

			}

		}

		/**
		 * Sell Access Page Setup
		 * @since 1.0
		 * @version 1.0
		 */
		public function sell_access_page() {

			add_action( 'bp_template_title',   array( $this, 'sell_access_title' ) );
			add_action( 'bp_template_content', array( $this, 'sell_access_screen' ) );

			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );

		}

		/**
		 * Sell Access Page Title
		 * @since 1.0
		 * @version 1.0
		 */
		public function sell_access_title() {

			echo bp_word_or_name( __( 'Stats', 'bp_charge' ), __( '%s\'s Stats', 'bp_charge' ), false, false );

		}

		/**
		 * Sell Access Page Screen
		 * @since 1.0
		 * @version 1.0
		 */
		public function sell_access_screen() {

			$profile_id = bp_displayed_user_id();
			$profile    = get_userdata( $profile_id );
			$settings   = $this->users_profile_setup( $profile_id );
			$buyers     = get_profile_buyer_avatars( $profile_id, $this->prefs['ctype'], $settings['expire'], 25, 100 );
			$stats      = mycred_bpc_get_profile_sale_stats( $profile_id, $settings );

?>
<table class="profile-fields">
	<tr class="field_1 field_name field_type_textbox">
		<td class="label"><?php _e( 'Total Sales', 'bp_charge' ); ?>:</td>
		<td class="data"><?php echo $this->core->format_creds( $stats['total_sales'] ); ?></td>
	</tr>
	<tr class="field_1 field_name field_type_textbox">
		<td class="label"><?php _e( 'Sales this month', 'bp_charge' ); ?>:</td>
		<td class="data"><?php echo $this->core->format_creds( $stats['total_sales_month'] ); ?></td>
	</tr>
	<tr class="field_1 field_name field_type_textbox">
		<td class="label"><?php _e( 'Total Buyers', 'bp_charge' ); ?>:</td>
		<td class="data"><?php echo abs( $stats['total_buyers'] ); ?></td>
	</tr>
	<tr class="field_1 field_name field_type_textbox">
		<td class="label"><?php _e( 'Buyers with access', 'bp_charge' ); ?>:</td>
		<td class="data"><?php echo abs( $stats['current_access'] ); ?></td>
	</tr>
</table>

<?php if ( $settings['expire'] > 0 ) : ?>

<h3><?php _e( 'Returning Buyers', 'bp_charge' ); ?></h3>
<table class="profile-fields">
	<tr class="field_1 field_name field_type_textbox">
		<td class="label"><?php _e( 'Top Buyer', 'bp_charge' ); ?>:</td>
		<?php if ( $stats['top_buyer'] !== NULL ) : ?>
		<td class="data"><a href="<?php echo bp_core_get_user_domain( $stats['top_buyer']->ID ); ?>"><?php echo $stats['top_buyer']->display_name; ?></a></td>
		<?php else : ?>
		<td class="data"><?php _e( 'No purchases yes', 'bp_charge' ); ?></td>
		<?php endif; ?>
	</tr>
</table>

<?php endif; ?>

<?php if ( ! empty( $buyers ) ) : ?>

<h3><?php printf( __( 'Buyers (%d)', 'bp_charge' ), count( $buyers ) ); ?></h3>
<div id="buyer-avatar-list">

<?php foreach ( $buyers as $buyer ) echo '<div class="buyer-avatar">' . $buyer . '</div>'; ?>

</div>

<?php endif; ?>

<?php do_action( 'mycred_bpc_stats_page', $profile_id, $this ); ?>

<?php

		}

		/**
		 * Sell Access Setup Page
		 * @since 1.0
		 * @version 1.0
		 */
		public function sell_access_setup_page() {

			add_action( 'bp_template_title',   array( $this, 'sell_acess_setup_title' ) );
			add_action( 'bp_template_content', array( $this, 'sell_access_setup_screen' ) );

			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );

		}

		/**
		 * Sell Access Setup Title
		 * @since 1.0
		 * @version 1.0
		 */
		public function sell_acess_setup_title() {

			echo bp_word_or_name( __( 'Setup', 'bp_charge' ), __( '%s\'s Setup', 'bp_charge' ), false, false );

		}

		/**
		 * Sell Access Setup Page Screen
		 * @since 1.0
		 * @version 1.1
		 */
		public function sell_access_setup_screen() {

			$profile_id = bp_displayed_user_id();
			$settings   = $this->users_profile_setup( $profile_id );

			$max = '';
			if ( $this->prefs['max_price'] > 0 )
				$max = '<span class="important">' . sprintf( __( 'Maximum %s', 'bp_charge' ), $this->core->format_creds( $this->prefs['max_price'] ) ) . '</span>';

?>
<div id="sell-access" class="profile" role="main">
	<form action="<?php echo add_query_arg( array( 'update-sell-access' => wp_create_nonce( 'update-bp-sell-access' ) ) ); ?>" method="post" class="standard-form">

		<?php do_action( 'mycred_bp_charges_after_setup_profile', $settings, $profile_id, $this ); ?>

		<div class="editfield field_1 enable_sales field_type_checkbox">
			<label for="sell-access-profile-enabled"><input type="checkbox" name="sell_access[enabled]"<?php checked( $settings['enabled'], 1 ); ?> id="sell-access-profile-enabled" value="1" /> <?php _e( 'Enable', 'bp_charge' ); ?></label>
		</div>
		<div class="editfield field_1 free_for_friends field_type_checkbox">
			<label for="sell-access-profile-free-friends"><input type="checkbox" name="sell_access[free_for_friends]"<?php checked( $settings['free_for_friends'], 1 ); ?> id="sell-access-profile-free-friends" value="1" /> <?php _e( 'Free for Friends', 'bp_charge' ); ?></label>
		</div>
		<div class="editfield field_2 sale_price field_type_textbox">
			<label for="sell-access-profile-"><?php _e( 'Price', 'bp_charge' ); ?></label>
			<?php echo $this->core->before; ?> <input type="text" name="sell_access[price]" value="<?php echo $this->core->number( $settings['price'] ); ?>" size="10" style="width: 100px;" /> <?php echo $this->core->after; ?>
			<p><span class="description"><?php echo $max; ?></span></p>
		</div>

		<?php if ( $this->prefs['fee'] != '' && $this->prefs['fee'] > 0 ) : ?>

		<p><?php printf( __( 'Note that you will be charged a service fee of %s for each sale.', 'bp_charge' ), absint( $this->prefs['fee'] ) . ' %' ); ?></p>

		<?php endif; ?>

		<?php if ( $this->prefs['default_expire'] > 0 ) : ?>

		<div class="editfield field_2 sale_price field_type_textbox">
			<label for="sell-access-profile-expire"><?php _e( 'Expiry', 'bp_charge' ); ?></label>
			<input type="number" min="0" max="356" name="sell_access[expire]" id="sell-access-profile-expire" value="<?php echo $settings['expire']; ?>" size="15" /> <?php _e( 'days', 'bp_charge' ); ?>
			<p><span class="description"><?php _e( 'Number of days that sales are valid. Once expired, the member must pay again to view your profile. Use zero for permanent sales.', 'bp_charge' ); ?></span></p>
		</div>

		<?php endif; ?>

		<?php do_action( 'mycred_bp_charges_after_setup_profile', $settings, $profile_id, $this ); ?>

		<input type="hidden" name="sell_access[user_id]" value="<?php echo $profile_id; ?>" />
		<input type="submit" class="btn btn-primary button button-primary" value="<?php _e( 'Save Changes', 'bp_charge' ); ?>" />
	</form>
</div>
<?php

		}

		/**
		 * Buy Access Page
		 * @since 1.0
		 * @version 1.0
		 */
		public function buy_access_page() {

			add_action( 'bp_template_title',   array( $this, 'buy_access_title' ) );
			add_action( 'bp_template_content', array( $this, 'buy_access_screen' ) );

			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );

		}

		/**
		 * Buy Access Page Title
		 * @since 1.0
		 * @version 1.0
		 */
		public function buy_access_title() {

			if ( $this->user_has_paid() )
				_e( 'Purchase History', 'bp_charges' );

		}

		/**
		 * Buy Access Page Screen
		 * @since 1.0
		 * @version 1.0
		 */
		public function buy_access_screen() {

			$cui        = get_current_user_id();
			$profile_id = bp_displayed_user_id();
			$profile    = get_userdata( $profile_id );
			$settings   = $this->users_profile_setup( $profile_id );

			$format       = get_option( 'date_format' );
			$now          = current_time( 'timestamp' );

			if ( $this->user_has_paid() ) :

				$last_payment = get_last_time_profile_was_purchased( $cui, $profile_id );

				if ( $settings['expire'] > 0 ) :

					$expires         = absint( $settings['expire'] );
					$seconds_in_diff = $now - $last_payment;
					$days            = floor( $seconds_in_diff / 86400 );
					$expire          = $expires-$days;

					$log_url = false;
					if ( isset( $mycred->buddypress['visibility']['history'] ) && $mycred->buddypress['visibility']['history'] == 1 )
						$log_url = bp_core_get_user_domain( $cui ) . '/' . $mycred->buddypress['history_url'] . '/';

?>

<?php do_action( 'mycred_bp_charges_before_profile_history', $profile_id, $this ); ?>

<table class="profile-fields">
	<tr class="field_1 field_name field_type_textbox">
		<td class="label"><?php _e( 'Last Payment', 'bp_charge' ); ?>:</td>
		<td class="data"><?php echo date_i18n( $format, $last_payment ); ?></td>
	</tr>
	<tr class="field_1 field_name field_type_textbox">
		<td class="label"><?php _e( 'Expires', 'bp_charge' ); ?>:</td>
		<td class="data"><?php printf( __( 'In %s days', 'bp_charge' ), $expire ); ?></td>
	</tr>
</table>

<?php if ( isset( $mycred->buddypress['visibility']['history'] ) && $mycred->buddypress['visibility']['history'] == 1 ) { ?>

<p><a href="<?php echo add_query_arg( array( 'ref' => 'profile_access', 'ref_id' => $profile->ID ), $log_url ); ?>">View complete purchase history</a></p>

<?php } ?>

<?php else : ?>

<table class="profile-fields">
	<tr class="field_1 field_name field_type_textbox">
		<td class="label"><?php _e( 'Unlocked', 'bp_charge' ); ?>:</td>
		<td class="data"><?php echo date_i18n( $format, $last_payment ); ?></td>
	</tr>
	<tr class="field_1 field_name field_type_textbox">
		<td class="label"><?php _e( 'Expires', 'bp_charge' ); ?>:</td>
		<td class="data"><?php _e( 'Never', 'bp_charge' ); ?></td>
	</tr>
</table>

<?php endif; ?>

<?php do_action( 'mycred_bp_charges_after_profile_history', $profile_id, $this ); ?>

<?php

			else :

				do_action( 'mycred_bp_charges_before_buy_profile', $profile_id, $this );

				display_profile_purchase_form( 'profile', $profile_id );

				do_action( 'mycred_bp_charges_after_buy_profile', $profile_id, $this );

			endif;

		}

		/**
		 * Buy Access
		 * @since 1.0
		 * @version 1.0
		 */
		public function buy_access_to_profile() {

			$cui        = get_current_user_id();
			$profile_id = absint( $_GET['buy-access-to-profile'] );

			if ( $cui != $profile_id && $this->profile_for_sale( $profile_id ) && ! $this->user_has_paid( $profile_id, $cui ) ) {

				$prefs   = $this->users_profile_setup( $profile_id );
				$balance = $this->core->get_users_balance( $cui, $this->prefs['ctype'] );

				// Exclusions
				if ( $this->core->exclude_user( $cui ) ) return;

				// Insufficient Funds
				if ( $balance < $prefs['price'] ) {

					// Redirect
					$url = remove_query_arg( array( 'buy-access-to-profile' ) );
					wp_redirect( add_query_arg( array( 'purchased' => -1 ), $url ) );
					exit;

				}

				// Charge
				else {

					// Charge buyer
					$this->core->add_creds(
						'profile_access',
						$cui,
						0 - $prefs['price'],
						$this->prefs['log_buy'],
						$profile_id,
						array( 'ref_type' => 'user' ),
						$this->prefs['ctype']
					);

					$pay_profile = true;
					// Fee
					$net = $prefs['price'];
					if ( $this->prefs['default_setup'] == 'off' ) {
						if ( $this->prefs['fee'] > 0 ) {
							$fee_amount = $this->core->number( ( ( $this->prefs['fee'] / 100 ) * $prefs['price'] ) );
							$net        = $this->core->number( $prefs['price'] - $fee_amount );
						}
					}
					else {
						// If we have a profit share, calculate it now
						if ( $this->prefs['profit_share'] > 0 )
							$net = $this->core->number( ( ( $this->prefs['profit_share'] / 100 ) * $prefs['price'] ) );

						// No profit share
						else $pay_profile = false;

					}

					if ( $pay_profile ) {

						// Deposit recipient
						$this->core->add_creds(
							'profile_access_sale',
							$profile_id,
							$net,
							$this->prefs['log_sell'],
							$cui,
							array( 'ref_type' => 'user' ),
							$this->prefs['ctype']
						);

						// Remove stats to force a new query
						mycred_delete_user_meta( $profile_id, '_profile_sale_stats' );

					}

					// Redirect
					$url = bp_core_get_user_domain( $profile_id ) . '/';
					wp_redirect( add_query_arg( array( 'purchased' => 1 ), $url ) );

					exit;

				}

			}
			else {

				// Redirect
				$url = remove_query_arg( array( 'buy-access-to-profile' ) );
				wp_redirect( add_query_arg( array( 'purchased' => -1 ), $url ) );

				exit;

			}

		}

		/**
		 * Save Profile Setup
		 * @since 1.0
		 * @version 1.0
		 */
		public function save_profile_setup() {

			$profile_id = bp_displayed_user_id();
			if ( ( $profile_id == $_POST['sell_access']['user_id'] ) || bp_current_user_can( 'bp_moderate' ) ) {

				$new_settings = array();

				// Enable / Disable
				if ( isset( $_POST['sell_access']['enabled'] ) )
					$new_settings['enabled'] = 1;
				else
					$new_settings['enabled'] = 0;

				// Free for friends
				if ( isset( $_POST['sell_access']['free_for_friends'] ) )
					$new_settings['free_for_friends'] = 1;
				else
					$new_settings['free_for_friends'] = 0;

				// Price
				$price = sanitize_text_field( $_POST['sell_access']['price'] );
				if ( $price == '' )
					$price = 0;

				$price = $this->core->number( $price );
				if ( $this->prefs['min_price'] != '' && $price < $this->prefs['min_price'] )
					$price = $this->prefs['min_price'];

				if ( $this->prefs['max_price'] != '' && $price > $this->prefs['max_price'] )
					$price = $this->prefs['max_price'];

				if ( $price == 0 )
					$price = 1;

				$new_settings['price'] = $price;

				// Expiration
				if ( isset( $_POST['sell_access']['expire'] ) ) {
					$expire = sanitize_text_field( $_POST['sell_access']['expire'] );
					if ( $expire == '' )
						$expire = $this->prefs['default_expire'];
				}
				else {
					$expire = 0;
				}

				$new_settings['expire'] = absint( $expire );

				// Save
				mycred_update_user_meta( $profile_id, 'sell_access_to_profile', '', $new_settings );

				// Redirect
				$url = remove_query_arg( array( 'update-sell-access' ) );
				wp_redirect( add_query_arg( array( 'saved' => 1 ), $url ) );

				exit;

			}

		}

		/**
		 * Profile Is For Sale?
		 * @since 1.0
		 * @version 1.0
		 */
		public function profile_for_sale( $profile_id = NULL ) {

			if ( $profile_id === NULL )
				$profile_id = bp_displayed_user_id();

			if ( $this->core->exclude_user( $profile_id ) ) return false;

			$result = false;

			// On by default
			if ( $this->prefs['default_setup'] == 'on' )
				$result = true;

			// Individual
			else {

				// Check profile owners setup
				$prefs = $this->users_profile_setup( $profile_id );
				if ( $prefs['enabled'] == 1 )
					$result = true;

				// Free for friends?
				if ( $result === true && bp_is_active( 'friends' ) && $prefs['free_for_friends'] == 1 && friends_check_friendship( $profile_id, get_current_user_id() ) )
					$result = false;

			}

			return apply_filters( 'mycred_is_profile_for_sale', $result, $this );

		}

		/**
		 * User Has Paid?
		 * @since 1.0
		 * @version 1.0
		 */
		public function user_has_paid( $profile_id = NULL, $user_id = NULL ) {

			if ( $profile_id === NULL )
				$profile_id = bp_displayed_user_id();

			if ( $user_id === NULL )
				$user_id = get_current_user_id();

			if ( $user_id == 0 ) return false;

			// Excluded user
			if ( $this->core->exclude_user( $user_id ) ) return false;

			// Free for admins
			if ( bp_current_user_can( 'bp_moderate' ) ) return true;

			$result = false;

			global $wpdb;

			$prefs = $this->users_profile_setup( $profile_id );

			// Purchases expire
			if ( $prefs['expire'] > 0 ) {

				$purchase = $wpdb->get_row( $wpdb->prepare( "
					SELECT * 
					FROM {$this->core->log_table} 
					WHERE ref = %s 
					AND user_id = %d 
					AND ref_id = %d
					AND time > %d 
					AND ctype = %s;", 'profile_access', $user_id, $profile_id, strtotime( '-' . $prefs['expire'] . ' days' ), $this->prefs['ctype'] ) );

				if ( isset( $purchase->id ) )
					$result = true;

			}

			// Final purchases
			else {

				$purchase = $wpdb->get_row( $wpdb->prepare( "
					SELECT * 
					FROM {$this->core->log_table} 
					WHERE ref = %s 
					AND user_id = %d 
					AND ref_id = %d 
					AND ctype = %s;", 'profile_access', $user_id, $profile_id, $this->prefs['ctype'] ) );

				if ( isset( $purchase->id ) )
					$result = true;

			}

			return apply_filters( 'mycred_user_paid_for_profile', $result, $profile_id, $user_id, $this );

		}

		/**
		 * Get Profile Setup
		 * @since 1.0
		 * @version 1.0
		 */
		public function users_profile_setup( $user_id ) {

			// Individual setup
			if ( $this->prefs['default_setup'] == 'off' ) {

				$default = array(
					'enabled'          => 0,
					'free_for_friends' => 0,
					'price'            => $this->prefs['default_price'],
					'expire'           => $this->prefs['default_expire']
				);

				$prefs = (array) mycred_get_user_meta( $user_id, 'sell_access_to_profile', '', true );
				$prefs = mycred_apply_defaults( $default, $prefs );

				if ( $this->prefs['default_expire'] == 0 )
					$prefs['expire'] = 0;

			}

			// On by default
			else {

				$prefs = array(
					'enabled'          => 1,
					'free_for_friends' => 0,
					'price'            => $this->prefs['price'],
					'expire'           => $this->prefs['default_expire']
				);

			}

			$profile_url = bp_core_get_user_domain( bp_displayed_user_id() );

			$prefs['insufficient'] = $this->prefs['cant_buy_template'];
			$prefs['content']      = $this->prefs['sale_template'];
			$prefs['buy_url']      = add_query_arg( array( 'buy-access-to-profile' => $user_id ), $profile_url . $this->prefs['buy_menu_slug'] . '/' );
			$prefs['view_url']     = $profile_url;

			return apply_filters( 'mycred_bp_charge_profile_setup', $prefs, $user_id, $this );

		}

		/**
		 * Preference for Charge
		 * @since 1.0
		 * @version 1.0
		 */
		public function preferences() {

			$prefs    = $this->prefs;
			$defaults = $this->defaults;
			$prefs    = mycred_apply_defaults( $defaults, $prefs );
			$types    = mycred_get_types();
			
			$before = '';
			if ( $this->core->before != '' )
				$before = $this->core->before . ' ';
			
			$after = '';
			if ( $this->core->after != '' )
				$after = ' ' . $this->core->after;

?>
<h3 class="subheader"><?php _e( 'Navigation Setup - Seller', 'bp_charge' ); ?></h3>
<ol class="inline">
	<li>
        <div class="form-group">
            <label><?php _e( 'Menu Title', 'bp_charge' ); ?></label>
            <input type="text" class="medium form-control" name="<?php echo $this->field_name( 'sell_menu_title' ); ?>" id="<?php echo $this->field_id( 'sell_menu_title' ); ?>" value="<?php echo $prefs['sell_menu_title']; ?>" />
        </div>
    </li>
	<li>
        <div class="form-group">
		    <label><?php _e( 'Menu URL Slug', 'bp_charge' ); ?></label>
		    <input type="text" class="form-control medium" name="<?php echo $this->field_name( 'sell_menu_slug' ); ?>" id="<?php echo $this->field_id( 'sell_menu_slug' ); ?>" value="<?php echo $prefs['sell_menu_slug']; ?>" />
        </div>
    </li>
	<li>
        <div class="form-group">
		    <label><?php _e( 'Menu Position', 'bp_charge' ); ?></label>
		    <input type="text" class="form-control short" name="<?php echo $this->field_name( 'sell_menu_pos' ); ?>" id="<?php echo $this->field_id( 'sell_menu_pos' ); ?>" value="<?php echo absint( $prefs['sell_menu_pos'] ); ?>" />
        </div>
    </li>
</ol>
<span class="description"><?php _e( 'Members can view their sales and edit their settings on this page.', 'bp_charge' ); ?></span>
<h3><?php _e( 'Navigation Setup - Buyer', 'bp_charge' ); ?></h3>
<ol class="inline">
	<li>
        <div class="form-group">
		    <label><?php _e( 'Menu Title', 'bp_charge' ); ?></label>
		    <input type="text" class="form-control medium" name="<?php echo $this->field_name( 'buy_menu_title' ); ?>" id="<?php echo $this->field_id( 'buy_menu_title' ); ?>" value="<?php echo $prefs['buy_menu_title']; ?>" />
        </div>
    </li>
	<li>
        <div class="form-group">
		    <label><?php _e( 'Menu URL Slug', 'bp_charge' ); ?></label>
            <input type="text" class="form-control medium" name="<?php echo $this->field_name( 'buy_menu_slug' ); ?>" id="<?php echo $this->field_id( 'buy_menu_slug' ); ?>" value="<?php echo $prefs['buy_menu_slug']; ?>" />
        </div>
    </li>
	<li>
        <div class="form-group">
		    <label><?php _e( 'Menu Position', 'bp_charge' ); ?></label>
		    <input type="text" class="form-control short" name="<?php echo $this->field_name( 'buy_menu_pos' ); ?>" id="<?php echo $this->field_id( 'buy_menu_pos' ); ?>" value="<?php echo absint( $prefs['buy_menu_pos'] ); ?>" />
        </div>
	</li>
</ol>
<span class="description"><?php _e( 'Members can buy access or view their purchase history on this page.', 'bp_charge' ); ?></span>
<h3><?php _e( 'Profile Buttons', 'bp_charge' ); ?></h3>
<ol>
	<li>
        <div class="form-group">
		    <select name="<?php echo $this->field_name( 'profile_buttons' ); ?>" id="<?php echo $this->field_id( 'profile_buttons' ); ?>" class="form-control">
                <?php
                $options = array(
                    0 => __( 'Show profile buttons even if user has not purchased access', 'bp_charge' ),
                    1 => __( 'Hide profile buttons if user has not purchased access', 'bp_charge' )
                );
                foreach ( $options as $value => $label ) {
                    echo '<option value="' . $value . '"';
                    if ( $prefs['profile_buttons'] == $value ) echo ' selected="selected"';
                    echo '>' . $label . '</option>';
                }
                ?>
		    </select>
        </div>
	</li>
</ol>
<h3><?php _e( 'Sales Setup', 'bp_charge' ); ?></h3>
<ol>
	<li>
        <div class="form-group">
		    <label for="<?php echo $this->field_id( 'default-setup-off' ); ?>"><input type="radio" name="<?php echo $this->field_name( 'default_setup' ); ?>" class="toggle-profile-setup" id="<?php echo $this->field_id( 'default-setup-off' ); ?>"<?php checked( $prefs['default_setup'], 'off' ); ?> value="off" /> <?php _e( 'Let users decide if they want to sell access to their profiles.', 'bp_charge' ); ?></label><br />
        </div>
        <div class="form-group">
            <label for="<?php echo $this->field_id( 'default-setup-on' ); ?>"><input type="radio" name="<?php echo $this->field_name( 'default_setup' ); ?>" class="toggle-profile-setup" id="<?php echo $this->field_id( 'default-setup-on' ); ?>"<?php checked( $prefs['default_setup'], 'on' ); ?> value="on" /> <?php _e( 'Set all profiles for sale by default.', 'bp_charge' ); ?></label>
        </div>
    </li>
	<li>
        <div class="form-group">
		    <span class="description"><?php _e( 'Note! If you set all profiles for sale, your users will not be able to set their own price or select to disable sales.', 'bp_charge' ); ?></span>
        </div>
    </li>
</ol>
<div id="charge-profile-off" class="charge-profile-setup" style="display:<?php if ( $prefs['default_setup'] == 'off' ) echo 'block'; else echo 'none'; ?>;">
    <h3 class="subheader"><?php _e( 'Prices', 'bp_charge' ); ?></h3>
    <ol class="inline">
        <li>
            <div class="form-group">
                <label class="subheader"><?php _e( 'Default Price', 'bp_charge' ); ?></label>
                <?php echo $before; ?><input type="text" class="form-control short" name="<?php echo $this->field_name( 'default_price' ); ?>" id="<?php echo $this->field_id( 'default_price' ); ?>" value="<?php echo esc_attr( $prefs['default_price'] ); ?>" /><?php echo $after; ?>
                <span class="description"><?php _e( 'Option to set a default price which will be suggested to your users.', 'bp_charge' ); ?></span>
            </div>
        </li>
        <li>
            <div class="form-group">
                <label><?php _e( 'Minimum', 'bp_charge' ); ?></label>
               <?php echo $before; ?><input type="text" class="form-control short" name="<?php echo $this->field_name( 'min_price' ); ?>" id="<?php echo $this->field_id( 'min_price' ); ?>" value="<?php echo esc_attr( $prefs['min_price'] ); ?>" /><?php echo $after; ?>
                <span class="description"><?php _e( 'The lowest price a user can set.', 'bp_charge' ); ?></span>
            </div>
        </li>
        <li>
            <div class="form-group">
                <label><?php _e( 'Maximum', 'bp_charge' ); ?></label>
                <?php echo $before; ?><input type="text" class="form-control short" name="<?php echo $this->field_name( 'max_price' ); ?>" id="<?php echo $this->field_id( 'max_price' ); ?>" value="<?php echo esc_attr( $prefs['max_price'] ); ?>" /><?php echo $after; ?>
                <span class="description"><?php _e( 'The highest price a user can set.', 'bp_charge' ); ?></span>
            </div>
        </li>
	</ol>
	<label class="subheader"><?php _e( 'Fee', 'bp_charge' ); ?></label>
	<ol>
		<li>
			<div class="form-group">
                <input type="text" class="form-control short" name="<?php echo $this->field_name( 'fee' ); ?>" id="<?php echo $this->field_id( 'fee' ); ?>" value="<?php echo esc_attr( $prefs['fee'] ); ?>" />
                <div style="display: inline-block; line-height: 45px;">%</div>
                <span class="description"><?php _e( 'Option to charge a percentage of the sale as a fee. This fee is taken out of the amount the profile owner set as their price. Use zero for no fee.', 'bp_charge' ); ?></span>
            </div>
        </li>
	</ol>
</div>
<div id="charge-profile-on" class="charge-profile-setup" style="display:<?php if ( $prefs['default_setup'] == 'on' ) echo 'block'; else echo 'none'; ?>;">
	<label class="subheader"><?php _e( 'Price', 'bp_charge' ); ?></label>
	<ol>
		<li>
			<div class="form-group">
               <?php echo $before; ?><input type="text" class="form-control short" name="<?php echo $this->field_name( 'price' ); ?>" id="<?php echo $this->field_id( 'price' ); ?>" value="<?php echo esc_attr( $prefs['price'] ); ?>" /><?php echo $after; ?>
			    <span class="description"><?php _e( 'Users can not change this price.', 'bp_charge' ); ?></span>
            </div>
        </li>
	</ol>
	<label class="subheader"><?php _e( 'Profit Share', 'bp_charge' ); ?></label>
	<ol>
		<li>
			<div class="form-group">
                <input type="text" class="short" name="<?php echo $this->field_name( 'profit_share' ); ?>" id="<?php echo $this->field_id( 'profit_share' ); ?>" value="<?php echo esc_attr( $prefs['profit_share'] ); ?>" /> %
			    <span class="description"><?php _e( 'Option to pay a percentage to the profile owner. Use zero to disable.', 'bp_charge' ); ?></span>
            </div>
        </li>
	</ol>
</div>
<label class="subheader"><?php _e( 'Point Type', 'bp_charge' ); ?></label>
<ol>
    <li>
        <div class="form-group mycred-bp-charges-width">
            <?php if ( count( $types ) == 1 ) echo $this->core->plural(); ?>
            <?php echo mycred_types_select_from_dropdown( $this->field_name( 'ctype' ), $this->field_id( 'ctype' ), $prefs['ctype'], false,' class="form-control"' ); ?>
        </div>
	</li>
</ol>
<label class="subheader"><?php _e( 'Purchase Expiry', 'bp_charge' ); ?></label>
<ol>
	<li>
		<div class="form-group">
            <input type="text" class="form-control short" name="<?php echo $this->field_name( 'default_expire' ); ?>" id="<?php echo $this->field_id( 'log_buy' ); ?>" value="<?php echo $prefs['default_expire']; ?>" />
            <div style="display: inline-block; line-height: 45px;"><?php _e( 'day(s)', 'bp_charge' ); ?></div>
		    <span class="description"><?php _e( 'Option to expire purchases forcing users to buy again. Use zero to only allow permanent sales. If set to zero, users will not be able to set an expire date. Set to at least 1 if you wan to allow users to use this feature.', 'bp_charge' ); ?></span>
        </div>
    </li>
</ol>
<label class="subheader"><?php _e( 'Non Member Template', 'bp_charge' ); ?></label>
<ol>
	<li>
		<?php wp_editor( $this->prefs['non_member_template'], 'nonmemberprofiletemplate', array( 'textarea_name' => $this->field_name( 'non_member_template' ), 'textarea_rows' => 15 ) ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li><?php echo $this->core->available_template_tags( array( 'general' ) ); ?> <?php _e( 'Used when a profile is viewed by a member that is not logged in to the website.', 'bp_charge' ); ?></li>
</ol>
<label class="subheader"><?php _e( 'Sale Template', 'bp_charge' ); ?></label>
<ol>
	<li>
		<?php wp_editor( $this->prefs['sale_template'], 'saletemplate', array( 'textarea_name' => $this->field_name( 'sale_template' ), 'textarea_rows' => 15 ) ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li><?php echo $this->core->available_template_tags( array( 'general', 'amount', 'user' ) ); ?> <?php printf( __( 'Use %s for the purchase button, %s to show when (if used) a purchase expires, %s to show the total number of buyers and %s to show the last 25 buyers avatar.', 'bp_charge' ), '<code>%button%</code>', '<code>%expires%</code>', '<code>%buyer_count%</code>', '<code>%buyers%</code>' ); ?></li>
</ol>
<label class="subheader"><?php _e( 'Insufficient Funds Template', 'bp_charge' ); ?></label>
<ol>
	<li>
		<p><?php _e( 'Shown when a user can not afford to pay to access a profile.', 'bp_charge' ); ?></p>
		<?php wp_editor( $this->prefs['cant_buy_template'], 'cantbuytemplate', array( 'textarea_name' => $this->field_name( 'cant_buy_template' ), 'textarea_rows' => 15 ) ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li><?php echo $this->core->available_template_tags( array( 'general', 'amount', 'user' ) ); ?> <?php printf( __( 'Use %s to show the current users balance, %s to show when (if used) a purchase expires, %s to show the total number of buyers and %s to show the last 25 buyers avatar.', 'bp_charge' ), '<code>%your_balance%</code>', '<code>%expires%</code>', '<code>%buyer_count%</code>', '<code>%buyers%</code>' ); ?></li>
</ol>
<label class="subheader"><?php _e( 'Purchase Log Template', 'bp_charge' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" class="form-control long" name="<?php echo $this->field_name( 'log_buy' ); ?>" id="<?php echo $this->field_id( 'log_buy' ); ?>" value="<?php echo esc_attr( $prefs['log_buy'] ); ?>" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'user' ) ); ?></span>
	</li>
</ol>
<label class="subheader"><?php _e( 'Sales Log Template', 'bp_charge' ); ?></label>
<ol>
	<li>
		<div class="h2"><input type="text" class="form-control long" name="<?php echo $this->field_name( 'log_sell' ); ?>" id="<?php echo $this->field_id( 'log_sell' ); ?>" value="<?php echo esc_attr( $prefs['log_sell'] ); ?>" /></div>
		<span class="description"><?php echo $this->available_template_tags( array( 'user' ) ); ?></span>
	</li>
</ol>
<script type="text/javascript">//<![CDATA[
jQuery(function($){

	$( 'input.toggle-profile-setup' ).click(function(){
		var box = $(this).val();
		$( 'div.charge-profile-setup' ).hide();
		$( 'div#charge-profile-' + box ).show();
	});

	$( '#<?php echo $this->field_id( 'hide_activities' ); ?>' ).change(function(){

		var selectedto = $(this).find( ':selected' );
		if ( selectedto.val() == 1 )
			$( '#bp-charge-activity-template' ).show();
		else
			$( '#bp-charge-activity-template' ).hide();

		console.log( selectedto.val() );

	});

});//]]>
</script>
<?php

		}

	}
endif;

?>