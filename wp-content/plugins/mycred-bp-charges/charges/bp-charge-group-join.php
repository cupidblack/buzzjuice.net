<?php
if ( ! defined( 'BP_CHARGES_VERSION' ) ) exit;

/**
 * Charge for Joining Group
 * @since 1.1
 * @version 1.0.1
 */
if ( ! class_exists( 'myCRED_BP_Charge_Join_Group' ) ) :
	class myCRED_BP_Charge_Join_Group extends myCRED_BP_Charge {

		/**
		 * Construct
		 */
		function __construct( $charge_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'join_group',
				'defaults' => array(
					'ctype'              => MYCRED_DEFAULT_TYPE_KEY,
					'log_buy'            => 'Group Access to %group_with_link%',
					'log_sell'           => 'Group Access purchase by %display_name%',
					'default_setup'      => 'off',
					'default_price'      => 1,
					'default_payout'     => 'founder',
					'min_price'          => 1,
					'max_price'          => 1000,
					'fee'                => 0,
					'price'              => 1,
					'sell_menu_title'    => 'Sell Access',
					'sell_menu_slug'     => 'sell-access',
					'sell_menu_pos'      => 75,
					'buy_menu_title'     => 'Buy Access',
					'buy_menu_slug'      => 'buy-access',
					'buy_menu_pos'       => 75,
					'sale_template'      => '<h1>Buy Access</h1>
<p class="bp-charges-message">Join this group for only <strong>%cred_f%</strong><br />
Purchases expires: <strong>%expires%</strong></p>
<p id="bp-charges-buy-button">%button%</p>',
					'cant_buy_template'  => '<h1>Buy Access</h1>
<p class="bp-charges-message">Insufficient funds.</p>',
					'non_member_template' => '<h1>Login to Buy Access</h1>
<p class="bp-charges-message">Join our community and gain access to groups.</p>',
					'hide_activities'   => 0,
					'activity_template' => '<a href="%buyurl%">Buy access</a> to view this groups activities'
				)
			), $charge_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.1
		 * @version 1.0
		 */
		public function run() {

			if ( isset( $this->prefs['ctype'] ) && $this->prefs['ctype'] != '' )
				$this->core = mycred( $this->prefs['ctype'] );

			$this->setup_minimum();

			add_action( 'bp_init',                                    array( $this, 'module_init' ), 120 );
			add_action( 'bp_actions',                                 array( $this, 'group_link_settings' ) );

			add_action( 'template_notices',                           array( $this, 'notices' ) );
			add_filter( 'template_redirect',                          array( $this, 'screen_override' ), 0, 2 );

			add_filter( 'mycred_parse_log_entry_group_access',        array( $this, 'log_entry_group_access' ), 10, 2 );
			add_filter( 'mycred_parse_log_entry_group_access_payout', array( $this, 'log_entry_group_payout' ), 10, 2 );

			add_filter( 'mycred_all_references',                      array( $this, 'add_badge_support' ), 82 );

			add_action( 'mycred_overview_after',                      array( $this, 'overview' ), 40 );

		}

		/**
		 * Add Badge Support
		 * @since 1.1.2
		 * @version 1.0
		 */
		public function add_badge_support( $references ) {

			$references['group_access']        = __( 'Group Access Payment (BP Charges)', 'bp_charge' );
			$references['group_access_payout'] = __( 'Group Access Payout (BP Charges)', 'bp_charge' );

			return $references;

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

			$access_url = add_query_arg( array( 'ref' => 'group_access' ), $url );
			$access     = $wpdb->get_var( $wpdb->prepare( "SELECT SUM( creds ) FROM {$mycred->log_table} WHERE ref = %s AND ctype = %s;", 'group_access', $this->prefs['ctype'] ) );
			$access     = ( $access === NULL ) ? 0 : abs( $access );

			$sale_url  = add_query_arg( array( 'ref' => 'group_access_payout' ), $url );
			$sale      = $wpdb->get_var( $wpdb->prepare( "SELECT SUM( creds ) FROM {$mycred->log_table} WHERE ref = %s AND ctype = %s;", 'group_access_payout', $this->prefs['ctype'] ) );
			$sale      = ( $sale === NULL ) ? 0 : $sale;

			$total     = $access - $sale;

?>
<div class="mycred-type clear first">
	<div class="module-title"><div class="type-icon"><div class="dashicons dashicons-groups"></div></div><?php _e( 'Group Access', 'bp_charge' ); ?><a href="<?php echo $url; ?>"><?php echo $mycred->format_creds( $total ); ?></a></div>
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
		 * Log Entry Group Access
		 * @since 1.1
		 * @version 1.0
		 */
		public function log_entry_group_access( $content, $log_entry ) {

			if ( $log_entry->ref_id == '' || $log_entry->ref_id == 0 ) {

				$content = str_replace( '%group_name%', 'n/a', $content );
				$content = str_replace( '%group_with_link%', 'n/a', $content );

			}

			else {

				$group           = $this->get_group_from_id( $log_entry->ref_id );
				$group_permalink = bp_get_group_permalink( $group );

				$content = str_replace( '%group_name%', $group->name, $content );
				$content = str_replace( '%group_with_link%', '<a href="' . $group_permalink . '">' . $group->name . '</a>', $content );

			}

			return $content;

		}

		/**
		 * Log Entry Group Payout
		 * @since 1.1
		 * @version 1.0
		 */
		public function log_entry_group_payout( $content, $log_entry ) {

			$content = $this->core->template_tags_user( $content, $log_entry->data );
			$group   = $this->get_group_from_id( $log_entry->ref_id );

			if ( isset( $group->id ) ) {

				$group_permalink = bp_get_group_permalink( $group );

				$content = str_replace( '%group_name%', $group->name, $content );
				$content = str_replace( '%group_with_link%', '<a href="' . $group_permalink . '">' . $group->name . '</a>', $content );

			}
			else {

				$content = str_replace( '%group_name%', 'n/a', $content );
				$content = str_replace( '%group_with_link%', 'n/a', $content );

			}

			return $content;

		}

		/**
		 * Setup Minimum
		 * @since 1.1
		 * @version 1.1
		 */
		protected function setup_minimum() {

			$lowest   = 1;
			$decimals = $this->core->format['decimals'] - 1;

			if ( $decimals > 0 ) {

				$lowest = '0.' . str_repeat( '0', $decimals ) . '1';
				$lowest = (float) $lowest;

			}

			$this->minimum = $lowest;

		}

		/**
		 * Module Init
		 * @since 1.1
		 * @version 1.0
		 */
		public function module_init() {

			if ( ! is_user_logged_in() ) return;

			// Joining a group by clicking on a button
			add_action( 'groups_member_before_save', array( $this, 'before_member_save' ) );
			add_action( 'groups_join_group',         array( $this, 'group_joined' ), 10, 2 );
			add_action( 'groups_accept_invite',      array( $this, 'invite_accepted' ), 10, 2 );

			// Joinging a group via the groups buy access page
			if ( bp_is_group() && bp_is_current_action( $this->prefs['buy_menu_slug'] ) && isset( $_GET['buy-access-to-group'] ) && $_GET['buy-access-to-group'] != '' )
				$this->buy_access_to_group();

			// Manage the join button label
			add_filter( 'bp_get_group_join_button',                 array( $this, 'join_button' ), 10, 2 );
			add_filter( 'bp_button_groups_group_membership_accept', array( $this, 'accept_invite_button' ), 999, 4 );

			// Save Group Setup
			if ( bp_is_group() && isset( $_POST['sell_access'] ) && isset( $_REQUEST['update-sell-access'] ) && wp_verify_nonce( $_REQUEST['update-sell-group-access'], 'update-bp-sell-group-access' ) )
				$this->save_group_setup();

		}

		/**
		 * Buy Access to Group
		 * Handles purchases made from the groups buy access page.
		 * @since 1.1
		 * @version 1.0
		 */
		public function buy_access_to_group() {

			$group    = groups_get_current_group();
			if ( ! isset( $group->id ) || ! is_numeric( $group->id ) ) return;

			$group_id = bp_get_group_id( $group );
			$settings = $this->group_setup( $group_id );

			if ( ! $this->is_member( $group_id ) && $this->group_for_sale( $group_id ) ) {

				$user_id         = bp_loggedin_user_id();
				$balance         = $this->core->get_users_balance( $user_id, $this->prefs['ctype'] );
				$group_permalink = bp_get_group_permalink( $group );

				if ( $balance < $settings['price'] ) {

					bp_core_add_message( __( 'Insufficient Funds. Could not join group.', 'bp_charge' ), 'error' );
					bp_core_redirect( $group_permalink . $this->prefs['buy_menu_slug'] . '/' );

				}

				else {

					if ( $this->charge_new_membership( $user_id, $group, $settings ) ) {

						groups_join_group( $group_id, get_current_user_id() );
						bp_core_add_message( __( 'You joined the group!', 'bp_charge' ) );

						bp_core_redirect( bp_get_group_permalink( $group ) );

					}
					else {

						bp_core_add_message( __( 'Could not charge for membership.', 'bp_charge' ), 'error' );
						bp_core_redirect( $group_permalink . $this->prefs['buy_menu_slug'] . '/' );

					}

				}

			}

		}

		/**
		 * Before Member is Saved
		 * Prevents a user from joining a group that is for sale and one
		 * they can not afford to join.
		 * @since 1.1
		 * @version 1.0
		 */
		public function before_member_save( $save ) {

			//if ( ! bp_is_action_variable( 'accept' ) ) return;

			if ( ! $this->group_for_sale( $save->group_id ) ) return;

			$settings = $this->group_setup( $save->group_id );
			$balance  = $this->core->get_users_balance( $save->user_id, $this->prefs['ctype'] );

			global $mycred_bp_charge_joining, $mycred_group_join_been_charged;

			$mycred_bp_charge_joining       = false;
			$mycred_group_join_been_charged = false;

			if ( $balance < $settings['price'] ) {
				$save->user_id = '';
			}
			else {
				$mycred_bp_charge_joining = true;
			}

		}

		/**
		 * Invite Accepted
		 * Used when a user joins a group via an invite
		 * @since 1.1
		 * @version 1.0
		 */
		public function invite_accepted( $user_id, $group_id ) {

			global $mycred_bp_charge_joining;

			if ( $mycred_bp_charge_joining === true ) {

				$group    = $this->get_group_from_id( $group_id );
				$settings = $this->group_setup( $group_id );

				$this->charge_new_membership( $user_id, $group, $settings );

			}

		}

		/**
		 * Group Joined
		 * Used when a user joins a public group
		 * @since 1.1
		 * @version 1.0
		 */
		public function group_joined( $group_id, $user_id ) {

			global $mycred_bp_charge_joining;

			if ( $mycred_bp_charge_joining === true ) {

				$group    = $this->get_group_from_id( $group_id );
				$settings = $this->group_setup( $group_id );

				$this->charge_new_membership( $user_id, $group, $settings );

			}

		}

		/**
		 * Join Group Button
		 * @since 1.1
		 * @version 1.0
		 */
		public function join_button( $button, $group ) {

			// No use if we already applied for membership and if this is not a group related button
			if ( in_array( $button['id'], array( 'membership_requested' ) ) || $button['component'] != 'groups' ) return $button;

			$user_id  = bp_loggedin_user_id();
			$balance  = 0;
			$group_id = bp_get_group_id( $group );
			$settings = $this->group_setup( $group_id );

			if ( ! $this->is_member( $group_id ) && $this->group_for_sale( $group_id ) ) {

				$balance = $this->core->get_users_balance( $user_id, $this->prefs['ctype'] );

				// Insufficient funds - hide button
				if ( $balance < $settings['price'] )
					$button = array();

				// All good, change labels and let BP do it's thing
				else {

					// Option to join a public group
					if ( $button['id'] == 'join_group' ) {

						$button['link_text']  = $settings['button'];
						$button['link_title'] = $settings['button'];

					}

					// Request membership to private group
					elseif ( $button['id'] == 'request_membership' ) {

						$button['link_text']  = $settings['button'];
						$button['link_title'] = $settings['button'];

					}

					// Accepting an invite
					elseif ( $button['id'] == 'accept_invite' ) {

						$button['link_text']  = $settings['button'];
						$button['link_title'] = $settings['button'];

					}

				}

			}

			return apply_filters( 'mycred_bp_charge_group_join_button', $button, $group_id, $settings, $balance, $button, $group );

		}

		/**
		 * Accept Invite Button
		 * @since 1.1
		 * @version 1.0
		 */
		public function accept_invite_button( $button, $object, $before, $after ) {

			$group    = groups_get_current_group();
			$user_id  = bp_loggedin_user_id();
			$balance  = 0;
			$group_id = bp_get_group_id( $group );
			$settings = $this->group_setup( $group_id );

			if ( ! $this->is_member( $group_id ) && $this->group_for_sale( $group_id ) ) {

				$balance = $this->core->get_users_balance( $user_id, $this->prefs['ctype'] );

				// Insufficient funds - hide button
				if ( $balance < $settings['price'] )
					return '';

				return $before . '<a'. $object->link_href . $object->link_title . $object->link_id . $object->link_rel . $object->link_class . '>' . $settings['button'] . '</a>' . $after;

			}

			return $button;

		}

		/**
		 * Group Link Setup
		 * @since 1.1
		 * @version 1.0.1
		 */
		public function group_link_settings() {

			global $bp;

			if ( ! bp_is_group() || bp_is_group_create() )
				return;

			$user_id         = bp_loggedin_user_id();

			$group           = groups_get_current_group();
			$group_id        = bp_get_group_id( $group );
			$group_permalink = bp_get_group_permalink( $group );

			if ( ! $this->is_member( $group_id ) && $this->group_for_sale( $group_id ) ) {

				bp_core_create_subnav_link( array(
					'name'            => $this->prefs['buy_menu_title'],
					'slug'            => $this->prefs['buy_menu_slug'],
					'parent_slug'     => bp_get_current_group_slug(),
					'parent_url'      => $group_permalink,
					'position'        => $this->prefs['buy_menu_pos'],
					'item_css_id'     => 'nav-' . $this->prefs['buy_menu_slug'],
					'screen_function' => array( $this, 'buy_access_page' ),
					'user_has_access' => true,
					'no_access_url'   => $group_permalink,
				) );

				bp_core_register_subnav_screen_function( array(
					'slug'            => $this->prefs['buy_menu_slug'],
					'parent_slug'     => bp_get_current_group_slug(),
					'screen_function' => array( $this, 'buy_access_page' ),
					'user_has_access' => true,
					'no_access_url'   => $group_permalink
				) );

			}

			if ( ! bp_is_item_admin() ) {
				return;
			}

			$admin_link    = trailingslashit( bp_get_group_permalink( $group ) . 'admin' );

			// Add the tab to the manage navigation
			bp_core_new_subnav_item( array(
				'name'              => $this->prefs['sell_menu_title'],
				'slug'              => $this->prefs['sell_menu_slug'],
				'parent_slug'       => $group->slug . '_manage',
				'parent_url'        => trailingslashit( bp_get_group_permalink( $group ) . 'admin' ),
				'user_has_access'   => bp_is_item_admin(),
				'position'          => $this->prefs['sell_menu_pos'],
				'screen_function'   => 'groups_screen_group_admin',
				'show_in_admin_bar' => true
			) );

			// Catch the edit screen and forward it to the plugin template
			if ( bp_is_groups_component() && bp_is_current_action( 'admin' ) && bp_is_action_variable( $this->prefs['sell_menu_slug'], 0 ) ) {

				$this->save_group_setup( $group_id );

				add_action( 'groups_custom_edit_steps', array( &$this, 'sell_access_setup_screen' ) );

				// Determine the proper template and save for later
				// loading
				if ( '' !== bp_locate_template( array( 'groups/single/home.php' ), false ) ) {
					$this->edit_screen_template = '/groups/single/home';
				} else {
					add_action( 'bp_template_content_header', create_function( '', 'echo "<ul class=\"content-header-nav\">"; bp_group_admin_tabs(); echo "</ul>";' ) );
					add_action( 'bp_template_content', array( &$this, 'sell_access_setup_screen' ) );
					$this->edit_screen_template = '/groups/single/plugins';
				}

				// We load the template at bp_screens, to give all
				// extensions a chance to load
				add_action( 'bp_screens', array( $this, 'sell_access_setup_page' ) );

			}

		}

		/**
		 * Buy Access Page
		 * @since 1.1
		 * @version 1.0
		 */
		public function buy_access_page() {

			add_action( 'bp_template_title',   array( $this, 'buy_access_title' ) );
			add_action( 'bp_template_content', array( $this, 'buy_access_screen' ) );

			bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );

		}

		/**
		 * Buy Access Page Title
		 * @since 1.1
		 * @version 1.0
		 */
		public function buy_access_title() {

			echo $this->prefs['buy_menu_title'];

		}

		/**
		 * Buy Access Page Screen
		 * @since 1.1
		 * @version 1.0
		 */
		public function buy_access_screen() {

			$user_id  = bp_loggedin_user_id();

			$group    = groups_get_current_group();
			$group_id = bp_get_group_id( $group );

			do_action( 'mycred_bp_charges_before_buy_group', $group_id, $this );

			display_profile_purchase_form( 'group', $group_id );

			do_action( 'mycred_bp_charges_after_buy_group', $group_id, $this );

		}

		/**
		 * Sell Access Setup Page
		 * @since 1.1
		 * @version 1.0
		 */
		public function sell_access_setup_page() {

			bp_core_load_template( $this->edit_screen_template );

		}

		/**
		 * Sell Access Setup Screen
		 * @since 1.1
		 * @version 1.0
		 */
		public function sell_access_setup_screen() {

			$user_id    = bp_loggedin_user_id();

			$group      = groups_get_current_group();
			$group_id   = bp_get_group_id( $group );
			$group_type = bp_get_group_status( $group );
			$settings   = $this->group_setup( $group_id );

			$button_label = __( 'Join Button Label', 'bp_charge' );
			if ( $group_type == 'private' )
				$button_label = __( 'Request Membership Button Label', 'bp_charge' );
			elseif ( $group_type == 'hidden' )
				$button_label = __( 'Accept Membership Invite Button Label', 'bp_charge' );

			$max = '';
			if ( $this->prefs['max_price'] > 0 )
				$max = '<span class="important">' . sprintf( __( 'Maximum %s', 'bp_charge' ), $this->core->format_creds( $this->prefs['max_price'] ) ) . '</span>';

?>
<div id="sell-access" class="profile" role="main">
	<form action="<?php echo add_query_arg( array( 'update-sell-access' => wp_create_nonce( 'update-bp-group-sell-access' ) ) ); ?>" method="post" class="standard-form">

		<?php do_action( 'mycred_bp_charges_before_setup_group', $settings, $group, $this ); ?>

		<div class="editfield field_1 enable_sales field_type_checkbox">
			<label for="sell-access-group-enabled"><input type="checkbox" name="sell_access[enabled]"<?php checked( $settings['enabled'], 1 ); ?> id="sell-access-group-enabled" value="1" /> <?php _e( 'Enable', 'bp_charge' ); ?></label>
		</div>
		<div class="editfield field_2 sale_price field_type_textbox">
			<label for="sell-access-group-price"><?php _e( 'Price', 'bp_charge' ); ?></label>
			<?php echo $this->core->before; ?> <input type="text" name="sell_access[price]" value="<?php echo $this->core->number( $settings['price'] ); ?>" id="sell-access-group-price" size="10" style="width: 100px;" /> <?php echo $this->core->after; ?>
			<p><span class="description"><?php echo $max; ?></span></p>
		</div>
		<div class="editfield field_3 button field_type_textbox">
			<label for="sell-access-group-button"><?php echo $button_label; ?></label>
			<?php echo $this->core->before; ?> <input type="text" name="sell_access[button]" value="<?php echo esc_attr( str_replace( '%price%', $this->core->format_creds( $settings['price'] ), $settings['button'] ) ); ?>" id="sell-access-group-button" style="width: 300px;" />
			<p><span class="description"><?php _e( 'This label is used in the Group directory.', 'bp_charge' ); ?></span></p>
		</div>

		<?php if ( $this->prefs['fee'] != '' && $this->prefs['fee'] > 0 ) : ?>

		<p><?php printf( __( 'Note that you will be charged a service fee of %s for each sale.', 'bp_charge' ), absint( $this->prefs['fee'] ) . ' %' ); ?></p>

		<?php endif; ?>

		<h4><?php _e( 'Profit Sharing', 'bp_charge' ); ?></h4>
		<div class="editfield field_4 payout-founder field_type_radio">
			<label for="sell-access-group-founder"><input type="radio" name="sell_access[payout]"<?php checked( $settings['payout'], 'founder' ); ?> id="sell-access-group-founder" value="founder" /> <?php _e( 'All payments are given to the group founder.', 'bp_charge' ); ?></label>
		</div>
		<div class="editfield field_5 payout-admins field_type_radio">
			<label for="sell-access-group-admins"><input type="radio" name="sell_access[payout]"<?php checked( $settings['payout'], 'admins' ); ?> id="sell-access-group-admins" value="admins" /> <?php _e( 'Payments are equally shared between admins.', 'bp_charge' ); ?></label>
		</div>
		<div class="editfield field_6 payout-mods field_type_radio">
			<label for="sell-access-group-mods"><input type="radio" name="sell_access[payout]"<?php checked( $settings['payout'], 'mods' ); ?> id="sell-access-group-mods" value="mods" /> <?php _e( 'Payments are equally shared between moderators.', 'bp_charge' ); ?></label>
		</div>
		<div class="editfield field_7 payout-members field_type_radio">
			<label for="sell-access-group-members"><input type="radio" name="sell_access[payout]"<?php checked( $settings['payout'], 'members' ); ?> id="sell-access-group-members" value="members" /> <?php _e( 'Payments are equally shared between members.', 'bp_charge' ); ?></label>
		</div>

		<?php do_action( 'mycred_bp_charges_after_setup_group', $settings, $group, $this ); ?>

		<input type="hidden" name="sell_access[group_id]" value="<?php echo $group_id; ?>" />
		<input type="submit" class="btn btn-primary button button-primary" value="<?php _e( 'Save Changes', 'bp_charge' ); ?>" />
	</form>
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
			if ( ! isset( $activity->component ) || $activity->component != 'groups' ) return $content;

			$current = get_current_user_id();
			$group_id = $activity->item_id;

			if ( bp_current_user_can( 'bp_moderate' ) ) return $content;

			$group = $this->get_group_from_id( $group_id );
			if ( $this->group_for_sale( $group_id ) && ! $this->is_member( $group_id ) ) {

				$content = $this->core->template_tags_general( $this->prefs['activity_template'] );
				$group_permalink = bp_get_group_permalink( $group_id );
				$content = str_replace( '%groupurl%', $group_permalink . $this->prefs['buy_menu_slug'] . '/', $content );

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

			if ( ! function_exists( 'bp_is_group' ) ) return;

			if ( ! bp_is_group() || bp_is_current_action( $this->prefs['buy_menu_slug'] ) || bp_is_current_action( 'join' ) ) return;

			$group           = groups_get_current_group();
			if ( ! isset( $group->id ) || ! is_numeric( $group->id ) ) return;

			$group_id        = bp_get_group_id( $group );
			$group_permalink = bp_get_group_permalink( $group );
			$override        = false;

			// Always override for visitors
			if ( ! is_user_logged_in() )
				$override = true;

			if ( ! $override ) {

				// Check if the current user has paid or needs to pay
				if ( ! $this->is_member( $group_id ) && $this->group_for_sale( $group_id ) )
					$override = true;

			}

			if ( $override ) {

				$url = $group_permalink . $this->prefs['buy_menu_slug'] . '/';

				wp_redirect( $url );
				exit;

			}

		}

		/**
		 * Notices
		 * @since 1.1
		 * @version 1.0
		 */
		public function notices() {

			if ( ! bp_is_group() ) return;

			$current_action = bp_current_action();
			if ( isset( $_GET['saved'] ) && $_GET['saved'] == 1 && $current_action == 'admin' )
				echo '<div id="message" class="bp-template-notice updated"><p>' . __( 'Settings Saved.', 'bp_charge' ) . '</p></div>';

			elseif ( isset( $_GET['purchased'] ) && $_GET['purchased'] == 1 )
				echo '<div id="message" class="bp-template-notice updated"><p>' . __( 'Purchase Completed.', 'bp_charge' ) . '</p></div>';

		}

		/**
		 * Get Group From ID
		 * @since 1.1
		 * @version 1.0
		 */
		public function get_group_from_id( $group_id = NULL ) {

			return groups_get_group( array(
				'group_id'        => $group_id,
				'populate_extras' => true
			) );

		}

		/**
		 * Is Member?
		 * @since 1.1
		 * @version 1.0
		 */
		public function is_member( $group_id ) {

			$member = false;
			if ( is_user_logged_in() && bp_is_user_active() ) {

				$user_id = bp_loggedin_user_id();
				if ( groups_is_user_admin( $user_id, $group_id ) || groups_is_user_mod( $user_id, $group_id ) || groups_is_user_member( $user_id, $group_id ) )
					$member = true;

			}

			return $member;

		}

		/**
		 * Group Is For Sale?
		 * @since 1.1
		 * @version 1.0
		 */
		public function group_for_sale( $group_id = NULL ) {

			$result = false;

			// On by default
			if ( $this->prefs['default_setup'] == 'on' )
				$result = true;

			// Individual
			else {

				// Check group setup
				$prefs = $this->group_setup( $group_id );
				if ( $prefs['enabled'] == 1 )
					$result = true;

			}

			return apply_filters( 'mycred_is_group_for_sale', $result, $this );

		}

		/**
		 * Charge New Membership
		 * @since 1.1
		 * @version 1.1
		 */
		public function charge_new_membership( $user_id, $group, $settings ) {

			global $mycred_group_join_been_charged;

			if ( $mycred_group_join_been_charged === true ) return true;

			// Lets charge teh user
			$charge = $this->core->add_creds(
				'group_access',
				$user_id,
				0 - $settings['price'],
				$this->prefs['log_buy'],
				$group->id,
				'',
				$this->prefs['ctype']
			);

			$mycred_group_join_been_charged = $charge;

			// Charge was successfull, time to payout?
			if ( $charge === true ) {

				// Fee
				$net = $settings['price'];
				if ( $this->prefs['default_setup'] == 'off' ) {

					if ( $this->prefs['fee'] > 0 ) {
						$fee_amount = $this->core->number( ( ( $this->prefs['fee'] / 100 ) * $settings['price'] ) );
						$net        = $this->core->number( $settings['price'] - $fee_amount );
					}

				}

				$share_holders = $this->get_share_holders( $settings['payout'], $group );

				if ( ! empty( $share_holders['users'] ) ) {

					$share = $net;
					if ( $share_holders['count'] > 1 ) {
						$share = floor( $net / $share_holders['count'] );
						$share = $this->core->number( $share );
					}

					foreach ( $share_holders['users'] as $shareholder_id ) {

						if ( $this->core->exclude_user( $shareholder_id ) || $share < $this->minimum ) continue;

						$this->core->add_creds(
							'group_access_payout',
							(int) $shareholder_id->user_id,
							$share,
							$this->prefs['log_sell'],
							$group->id,
							$user_id,
							$this->prefs['ctype']
						);

					}
				}

			}

			return $charge;

		}

		/**
		 * Get Share Holders
		 * @since 1.1
		 * @version 1.0
		 */
		public function get_share_holders( $type = '', $group = NULL ) {

			if ( $type == 'founder' ) {

				$founder = new stdClass();
				$founder->user_id = $group->creator_id;

				return array(
					'users' => array( $founder ),
					'count' => 1
				);

			}

			global $wpdb;

			$groups_table = $wpdb->prefix . 'bp_groups_members';

			if ( $type == 'admins' ) {

				$results = $wpdb->get_results( $wpdb->prepare( "

					SELECT groups.user_id

					FROM {$groups_table} groups 

					WHERE groups.group_id       = %d 
						AND groups.is_admin     = 1 
						AND groups.is_confirmed = 1 
						AND groups.is_banned    = 0 

					ORDER BY groups.user_id DESC;", $group->id ) );

				return array(
					'users' => $results,
					'count' => count( $results )
				);

			}

			if ( $type == 'mods' ) {

				$results = $wpdb->get_results( $wpdb->prepare( "

					SELECT groups.user_id

					FROM {$groups_table} groups 

					WHERE groups.group_id       = %d 
						AND groups.is_mod       = 1 
						AND groups.is_confirmed = 1 
						AND groups.is_banned    = 0 

					ORDER BY groups.user_id DESC;", $group->id ) );

				return array(
					'users' => $results,
					'count' => count( $results )
				);

			}

			if ( $type == 'members' ) {

				$results = $wpdb->get_results( $wpdb->prepare( "

					SELECT groups.user_id

					FROM {$groups_table} groups 

					WHERE groups.group_id       = %d 
						AND groups.is_mod       = 0 
						AND groups.is_admin     = 0 
						AND groups.is_confirmed = 1 
						AND groups.is_banned    = 0 

					ORDER BY groups.user_id DESC;", $group->id ) );

				return array(
					'users' => $results,
					'count' => count( $results )
				);

			}

			return array();

		}

		/**
		 * Refund Membership
		 * @since 1.1
		 * @version 1.0
		 */
		public function refund_membership( $user_id = NULL, $group = NULL, $cost = 1 ) {

			global $wpdb;

			$payment = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->core->log_table} WHERE ref = 'group_access' AND user_id = %d AND ref_id = %d ORDER BY time DESC LIMIT 0,1;", $user_id, $group->id ) );

			if ( isset( $payment->id ) ) {

				$this->core->update_users_balance( $user_id, abs( $payment->creds ) );

				$wpdb->delete(
					$this->core->log_table,
					array( 'id' => $payment->id ),
					array( '%d' )
				);

				$shares = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->core->log_table} WHERE ref = 'group_access_payout' AND data = %d AND ref_id = %d", $user_id, $group->id ) );

				if ( ! empty( $shares ) ) {
					foreach ( $shares as $entry ) {

						$this->core->update_users_balance( $entry->user_id, abs( $entry->creds ) );

						$wpdb->delete(
							$this->core->log_table,
							array( 'id' => $entry->id ),
							array( '%d' )
						);

					}
				}

			}

			return false;

		}

		/**
		 * Get Profile Setup
		 * @since 1.1
		 * @version 1.0
		 */
		public function group_setup( $group_id ) {

			$group_url = bp_get_group_permalink( groups_get_group( array( 'group_id' => $group_id ) ) );

			// Individual setup
			if ( $this->prefs['default_setup'] == 'off' ) {

				$default = array(
					'enabled' => 0,
					'price'   => $this->prefs['default_price'],
					'payout'  => 'founder',
					'button'  => 'Join for %price%'
				);

				$prefs = (array) groups_get_groupmeta( $group_id, 'bp_charges_group' );
				$prefs = mycred_apply_defaults( $default, $prefs );

			}

			// On by default
			else {

				$prefs = array(
					'enabled' => 1,
					'price'   => $this->prefs['price'],
					'payout'  => $this->prefs['default_payout'],
					'button'  => 'Join for %price%'
				);

			}

			$prefs['insufficient'] = $this->prefs['cant_buy_template'];
			$prefs['content']      = $this->prefs['sale_template'];
			$prefs['buy_url']      = add_query_arg( array( 'buy-access-to-group' => $group_id ), $group_url . $this->prefs['buy_menu_slug'] . '/' );

			return apply_filters( 'mycred_bp_charge_group_setup', $prefs, $group_id, $this );

		}

		/**
		 * Save Profile Setup
		 * @since 1.1
		 * @version 1.0
		 */
		public function save_group_setup( $group_id ) {

			if ( isset( $_POST['sell_access']['group_id'] ) && ( $group_id == $_POST['sell_access']['group_id'] ) && ( bp_is_item_admin() || bp_current_user_can( 'bp_moderate' ) ) ) {

				$new_settings = array();

				// Enable / Disable
				if ( isset( $_POST['sell_access']['enabled'] ) )
					$new_settings['enabled'] = 1;
				else
					$new_settings['enabled'] = 0;

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

				if ( isset( $_POST['sell_access']['payout'] ) )
					$new_settings['payout'] = sanitize_key( $_POST['sell_access']['payout'] );
				else
					$new_settings['payout'] = $this->prefs['default_payout'];

				if ( isset( $_POST['sell_access']['button'] ) )
					$new_settings['button'] = sanitize_text_field( $_POST['sell_access']['button'] );
				else
					$new_settings['button'] = 'Join for %price%';

				// Save
				groups_update_groupmeta( $group_id, 'bp_charges_group', $new_settings );

				// Redirect
				$url = remove_query_arg( array( 'update-sell-access' ) );
				wp_redirect( add_query_arg( array( 'saved' => 1 ), $url ) );

				exit;

			}

		}

		/**
		 * Preference for Charge
		 * @since 1.1
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
<h3><?php _e( 'Navigation Setup - Seller', 'bp_charge' ); ?></h3>
<ol class="inline">
	<li>
		<label><?php _e( 'Menu Title', 'bp_charge' ); ?></label>
        <div class="h2"><input type="text" class="form-control medium" name="<?php echo $this->field_name( 'sell_menu_title' ); ?>" id="<?php echo $this->field_id( 'sell_menu_title' ); ?>" value="<?php echo esc_attr( $prefs['sell_menu_title'] ); ?>" /></div>
    </li>
	<li>
		<label><?php _e( 'Menu URL Slug', 'bp_charge' ); ?></label>
		<div class="h2"><input type="text" class="form-control medium" name="<?php echo $this->field_name( 'sell_menu_slug' ); ?>" id="<?php echo $this->field_id( 'sell_menu_slug' ); ?>" value="<?php echo esc_attr( $prefs['sell_menu_slug'] ); ?>" /></div>
	</li>
	<li>
		<label><?php _e( 'Menu Position', 'bp_charge' ); ?></label>
		<div class="h2"><input type="text" class="form-control short" name="<?php echo $this->field_name( 'sell_menu_pos' ); ?>" id="<?php echo $this->field_id( 'sell_menu_pos' ); ?>" value="<?php echo absint( $prefs['sell_menu_pos'] ); ?>" /></div>
	</li>
</ol>
<span class="description"><?php _e( 'Group administrators an enable and setup sales from this page.', 'bp_charge' ); ?></span>
<h3><?php _e( 'Navigation Setup - Buyer', 'bp_charge' ); ?></h3>
<ol class="inline">
	<li>
		<label><?php _e( 'Menu Title', 'bp_charge' ); ?></label>
		<div class="h2"><input type="text" class="form-control medium" name="<?php echo $this->field_name( 'buy_menu_title' ); ?>" id="<?php echo $this->field_id( 'buy_menu_title' ); ?>" value="<?php echo esc_attr( $prefs['buy_menu_title'] ); ?>" /></div>
	</li>
	<li>
		<label><?php _e( 'Menu URL Slug', 'bp_charge' ); ?></label>
		<div class="h2"><input type="text" class="form-control medium" name="<?php echo $this->field_name( 'buy_menu_slug' ); ?>" id="<?php echo $this->field_id( 'buy_menu_slug' ); ?>" value="<?php echo esc_attr( $prefs['buy_menu_slug'] ); ?>" /></div>
	</li>
	<li>
		<label><?php _e( 'Menu Position', 'bp_charge' ); ?></label>
		<div class="h2"><input type="text" class="form-control short" name="<?php echo $this->field_name( 'buy_menu_pos' ); ?>" id="<?php echo $this->field_id( 'buy_menu_pos' ); ?>" value="<?php echo absint( $prefs['buy_menu_pos'] ); ?>" /></div>
	</li>
</ol>
<span class="description" style="margin-bottom: 15px;"><?php _e( 'Members can buy access to the group from this page.', 'bp_charge' ); ?></span>
<ol class="inline">
    <li>
        <label style="line-height: 40px;"><?php _e( 'Point Type', 'bp_charge' ); ?></label>
    </li>
	<li>

		<?php if ( count( $types ) == 1 ) echo $this->core->plural(); ?>

		<?php echo mycred_types_select_from_dropdown( $this->field_name( 'ctype' ), $this->field_id( 'ctype' ), $prefs['ctype'], false, ' class="form-control"' ); ?>

	</li>
</ol>
<h3><?php _e( 'Sales Setup', 'bp_charge' ); ?></h3>
<ol>
	<li>
		<label for="<?php echo $this->field_id( 'default-setup-off' ); ?>"><input type="radio" name="<?php echo $this->field_name( 'default_setup' ); ?>" class="toggle-group-setup" id="<?php echo $this->field_id( 'default-setup-off' ); ?>"<?php checked( $prefs['default_setup'], 'off' ); ?> value="off" /> <?php _e( 'Let group admins decide if they want to sell access to their groups.', 'bp_charge' ); ?></label><br />
		<label for="<?php echo $this->field_id( 'default-setup-on' ); ?>"><input type="radio" name="<?php echo $this->field_name( 'default_setup' ); ?>" class="toggle-group-setup" id="<?php echo $this->field_id( 'default-setup-on' ); ?>"<?php checked( $prefs['default_setup'], 'on' ); ?> value="on" /> <?php _e( 'Set all group access for sale by default.', 'bp_charge' ); ?></label>
	</li>
	<li>
		<span class="description"><?php _e( 'Note! If you set all groups for sale, your users will not be able to set their own price or select to disable sales.', 'bp_charge' ); ?></span>
	</li>
</ol>
<div id="charge-group-off" class="charge-group-setup" style="display:<?php if ( $prefs['default_setup'] == 'off' ) echo 'block'; else echo 'none'; ?>;">
	<h3>Prices</h3>
    <ol class="inline">
        <li>
            <label><?php _e( 'Default Price', 'bp_charge' ); ?></label>
            <div class="h2"><?php echo $before; ?><input type="text" class="form-control short" name="<?php echo $this->field_name( 'default_price' ); ?>" id="<?php echo $this->field_id( 'default_price' ); ?>" value="<?php echo esc_attr( $prefs['default_price'] ); ?>" /><?php echo $after; ?></div>
			<span class="description"><?php _e( 'Option to set a default price which will be suggested to your users.', 'bp_charge' ); ?></span>
		</li>
		<li>
			<label><?php _e( 'Minimum', 'bp_charge' ); ?></label>
			<div class="h2"><?php echo $before; ?><input type="text" class="form-control short" name="<?php echo $this->field_name( 'min_price' ); ?>" id="<?php echo $this->field_id( 'min_price' ); ?>" value="<?php echo esc_attr( $prefs['min_price'] ); ?>" /><?php echo $after; ?></div>
			<span class="description"><?php _e( 'The lowest price a user can set.', 'bp_charge' ); ?></span>
		</li>
		<li>
			<label><?php _e( 'Maximum', 'bp_charge' ); ?></label>
			<div class="h2"><?php echo $before; ?><input type="text" class="form-control short" name="<?php echo $this->field_name( 'max_price' ); ?>" id="<?php echo $this->field_id( 'max_price' ); ?>" value="<?php echo esc_attr( $prefs['max_price'] ); ?>" /><?php echo $after; ?></div>
			<span class="description"><?php _e( 'The highest price a user can set.', 'bp_charge' ); ?></span>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Fee', 'bp_charge' ); ?></label>
	<ol>
		<li>
            <div class="h2"><input type="text" class="form-control short" name="<?php echo $this->field_name( 'fee' ); ?>" id="<?php echo $this->field_id( 'fee' ); ?>" value="<?php echo esc_attr( $prefs['fee'] ); ?>" /> <div style="display: inline-block; line-height: 45px;"> %</div></div>
			<span class="description"><?php _e( 'Option to charge a percentage of the sale as a fee. This fee is taken out of the amount a member pays to join the group. Use zero for no fee.', 'bp_charge' ); ?></span>
		</li>
	</ol>
</div>
<div id="charge-group-on" class="charge-group-setup" style="display:<?php if ( $prefs['default_setup'] == 'on' ) echo 'block'; else echo 'none'; ?>;">
	<label class="subheader"><?php _e( 'Price', 'bp_charge' ); ?></label>
	<ol>
		<li>
			<div class="h2"><?php echo $before; ?><input type="text" class="short" name="<?php echo $this->field_name( 'price' ); ?>" id="<?php echo $this->field_id( 'price' ); ?>" value="<?php echo esc_attr( $prefs['price'] ); ?>" /><?php echo $after; ?></div>
			<span class="description"><?php _e( 'Users can not change this price.', 'bp_charge' ); ?></span>
		</li>
	</ol>
	<label class="subheader"><?php _e( 'Profit Share', 'bp_charge' ); ?></label>
	<ol>
		<li>
			<label for="<?php echo $this->field_id( 'default_payout' ); ?>"><input type="radio" name="<?php echo $this->field_name( 'default_payout' ); ?>"<?php checked( $prefs['default_payout'], 'founder' ); ?> id="<?php echo $this->field_id( 'default_payout' ); ?>" value="founder" /> <?php _e( 'All payments are given to the group founder.', 'bp_charge' ); ?></label>
		</li>
		<li>
			<label for="<?php echo $this->field_id( 'default_payout' ); ?>"><input type="radio" name="<?php echo $this->field_name( 'default_payout' ); ?>"<?php checked( $prefs['default_payout'], 'admins' ); ?> id="<?php echo $this->field_id( 'default_payout' ); ?>" value="admins" /> <?php _e( 'Payments are equally shared between admins.', 'bp_charge' ); ?></label>
		</li>
		<li>
			<label for="<?php echo $this->field_id( 'default_payout' ); ?>"><input type="radio" name="<?php echo $this->field_name( 'default_payout' ); ?>"<?php checked( $prefs['default_payout'], 'mods' ); ?> id="<?php echo $this->field_id( 'default_payout' ); ?>" value="mods" /> <?php _e( 'Payments are equally shared between moderators.', 'bp_charge' ); ?></label>
		</li>
		<li>
			<label for="<?php echo $this->field_id( 'default_payout' ); ?>"><input type="radio" name="<?php echo $this->field_name( 'default_payout' ); ?>"<?php checked( $prefs['default_payout'], 'members' ); ?> id="<?php echo $this->field_id( 'default_payout' ); ?>" value="members" /> <?php _e( 'Payments are equally shared between members.', 'bp_charge' ); ?></label>
		</li>
	</ol>
</div>
<h3><?php _e( 'Purchase Expiry', 'bp_charge' ); ?></h3>
<ol>
	<li>
		<span class="description"><?php _e( 'Purchase expiry is not supported for group memberships.', 'bp_charge' ); ?></span>
	</li>
</ol>
<h3><?php _e( 'Non Member Template', 'bp_charge' ); ?></h3>
<ol>
	<li>
		<?php wp_editor( $this->prefs['non_member_template'], 'nonmembergrouptemplate', array( 'textarea_name' => $this->field_name( 'non_member_template' ), 'textarea_rows' => 15 ) ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li><?php echo $this->core->available_template_tags( array( 'general' ) ); ?> <?php _e( 'Used when a group is viewed by a member that is not logged in to the website.', 'bp_charge' ); ?></li>
</ol>
<h3><?php _e( 'Sale Template', 'bp_charge' ); ?></h3>
<ol>
	<li>
		<?php wp_editor( $this->prefs['sale_template'], 'salegrouptemplate', array( 'textarea_name' => $this->field_name( 'sale_template' ), 'textarea_rows' => 15 ) ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li><?php echo $this->core->available_template_tags( array( 'general', 'amount', 'user' ) ); ?> <?php printf( __( 'Use %s for the purchase button and %s to show when (if used) a purchase expires', 'bp_charge' ), '<code>%button%</code>', '<code>%expires%</code>' ); ?></li>
</ol>
<h3><?php _e( 'Insufficient Funds Template', 'bp_charge' ); ?></h3>
<ol>
	<li>
		<p><?php _e( 'Shown when a user can not afford to pay to access a profile.', 'bp_charge' ); ?></p>
		<?php wp_editor( $this->prefs['cant_buy_template'], 'cantbuygrouptemplate', array( 'textarea_name' => $this->field_name( 'cant_buy_template' ), 'textarea_rows' => 15 ) ); ?>
	</li>
	<li class="empty">&nbsp;</li>
	<li><?php echo $this->core->available_template_tags( array( 'general', 'amount', 'user' ) ); ?> <?php printf( __( 'Use %s to show the current users balance and %s to show when (if used) a purchase expires.', 'bp_charge' ), '<code>%your_balance%</code>', '<code>%expires%</code>' ); ?></li>
</ol>
<h3><?php _e( 'Purchase Log Template', 'bp_charge' ); ?></h3>
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

	$( 'input.toggle-group-setup' ).click(function(){
		var box = $(this).val();
		$( 'div.charge-group-setup' ).hide();
		$( 'div#charge-group-' + box ).show();
	});

	$( '#<?php echo $this->field_id( 'hide_activities' ); ?>' ).change(function(){

		var selectedto = $(this).find( ':selected' );
		if ( selectedto.val() == 1 )
			$( '#bp-charge-group-activity-template' ).show();
		else
			$( '#bp-charge-group-activity-template' ).hide();

		console.log( selectedto.val() );

	});

});//]]>
</script>
<?php

		}

	}
endif;

?>