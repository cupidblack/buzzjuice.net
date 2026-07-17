<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'myCRED_Toolkit_BP_Group_Leaderboards' ) ) :
	#[AllowDynamicProperties]
	final class myCRED_Toolkit_BP_Group_Leaderboards {


		// Instnace
		protected static $_instance = null;

		// Current session
		public $session             = null;

	
		public $plugin_name         = '';

		/**
		 * Setup Instance
		 * @since 1.0
		 * @version 1.0
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}
			return self::$_instance;
		}

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __clone() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.2.8' ); }

		/**
		 * Not allowed
		 * @since 1.0
		 * @version 1.0
		 */
		public function __wakeup() {
 _doing_it_wrong( __FUNCTION__, 'Cheatin&#8217; huh?', '1.2.8' ); }

		/**
		 * Define
		 * @since 1.0
		 * @version 1.0
		 */
		private function define( $name, $value, $definable = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * Require File
		 * @since 1.0
		 * @version 1.0
		 */
		public function file( $required_file ) {
			if ( file_exists( $required_file ) ) {
				require_once $required_file;
			}
		}

		/**
		 * Construct
		 * @since 1.0
		 * @version 1.1
		 */
		public function __construct() {

			$this->slug        = 'mycred-bp-group-leaderboards';
			$this->plugin      = plugin_basename( __FILE__ );
			$this->domain      = 'mycred-toolkit';
			$this->plugin_name = 'myCRED BP Group Leaderboards';

			$this->define_constants();

			global $reset_bp_group_leaderboard;

			$reset_bp_group_leaderboard = false;

			add_action( 'bp_init', array( $this, 'setup_bp' ) );
			add_filter( 'mycred_add_finished', array( $this, 'reset_users_leaderboards' ), 999, 2 );

			// Remove caches when a point type is removed
			add_action( 'mycred_delete_point_type', array( $this, 'delete_cached_leaderboards' ) );
			add_action( 'mycred_bp_leaderboard_remove_type', array( $this, 'delete_cached_leaderboards' ) );
		}

		/**
		 * Define Constants
		 * @since 1.0
		 * @version 1.0
		 */
		public function define_constants() {

			
			$this->define( 'MYCRED_BP_GROUP_LEAD_SLUG', $this->slug );
			$this->define( 'MYCRED_DEFAULT_TYPE_KEY', 'mycred_default' );
			$this->define( 'MYCRED_BP_GROUP_LEAD_MAX_SIZE', 50 );

			$this->define( 'MYCRED_BP_GROUP_LEAD_ROOT', plugin_dir_path( __FILE__ ) );
			$this->define( 'MYCRED_BP_GROUP_LEAD_INCLUDES', MYCRED_BP_GROUP_LEAD_ROOT . 'includes/' );
		}

		/**
		 * Setup BuddyPress Extention
		 * @since 1.0
		 * @version 1.0
		 */
		public function setup_bp() {

			if ( ! bp_is_active( 'groups' ) || ! function_exists( 'mycred' ) ) {
return;
			}

			$this->file( MYCRED_BP_GROUP_LEAD_INCLUDES . 'bpgroup-leaderboard-functions.php' );
			$this->file( MYCRED_BP_GROUP_LEAD_INCLUDES . 'bpgroup-leaderboard.php' );

			bp_register_group_extension( 'myCRED_Group_Leaderboards' );

			if ( is_multisite() && ! is_network_admin() ) {
				add_action( 'admin_init', array( $this, 'register_settings' ) );
				add_filter( 'bp_core_admin_tabs', array( $this, 'add_settings_tab' ) );
				add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
				add_action( 'network_admin_menu', array( $this, 'add_settings_page' ) );
			} elseif ( ! is_multisite() ) {
				add_action( 'admin_init', array( $this, 'register_settings' ) );
				add_filter( 'bp_core_admin_tabs', array( $this, 'add_settings_tab' ) );
				add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
				add_action( 'bp_admin_head', array( $this, 'admin_head' ), 900 );
			}

			add_action( 'groups_join_group', array( $this, 'reset_group_leaderboard' ) );
			add_action( 'groups_leave_group', array( $this, 'reset_group_leaderboard' ) );
		}

		/**
		 * Reset Users Leaderboards
		 * Deletes the leaderboard caches for each group a user is a member of when they gain or lose points.
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function reset_users_leaderboards( $results, $request ) {

			// In case no points were given / taken or if BuddyPress Groups got disabled, bail
			if ( $results === false || ! function_exists( 'groups_get_user_groups' ) ) {
return $results;
			}

			$group_memberships = groups_get_user_groups( $request['user_id'] );

			// Delete all group leaderboard caches the user is a member of
			// This will force a new leaderboard to be saved the next time someone views the leaderboard page
			if ( ! empty( $group_memberships['groups'] ) ) {
				foreach ( $group_memberships['groups'] as $group_id ) {

					groups_delete_groupmeta( $group_id, 'cached_leaderboard' . $request['type'] );

					delete_user_meta( $request['user_id'], '_bp_leaderboard_position' . $group_id );

				}
			}

			return $results;
		}

		/**
		 * Reset Group Leaderboard
		 * Deletes the leaderboard cache when a user joined or leaves a group.
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function reset_group_leaderboard( $group_id ) {

			$settings = mycred_get_bp_group_setup( $group_id );
			if ( empty( $settings['types'] ) ) {
return;
			}

			// Delete cached leaderboards
			foreach ( $settings['types'] as $point_type ) {
				$this->delete_cached_leaderboards( $point_type );
			}

			global $wpdb;

			// Delete positions for all group members
			$wpdb->delete(
				$wpdb->usermeta,
				array( 'meta_key' => '_bp_leaderboard_position' . $group_id ),
				array( '%s' )
			);
		}

		/**
		 * Delete Cached Leaderboards
		 * @since 1.1
		 * @version 1.0
		 */
		public function delete_cached_leaderboards( $point_type ) {

			global $wpdb;

			// Delete caches
			$wpdb->delete(
				$wpdb->prefix . 'bp_groups_groupmeta',
				array( 'meta_key' => 'cached_leaderboard' . $point_type ),
				array( '%s' )
			);
		}

		/**
		 * Register Settings
		 * @since 1.0
		 * @version 1.0
		 */
		public function register_settings() {

			register_setting( 'bp-leaderboards', 'mycred_bp_leaderboards', array( $this, 'sanitize_settings' ) );
		}

		/**
		 * Add Settings Tab
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_settings_tab( $tabs ) {

			$tabs['99'] = array(
				'href' => bp_get_admin_url( add_query_arg( array( 'page' => 'bp-leaderboards' ), 'admin.php' ) ),
				'name' => __( 'Leaderboards', 'mycred-toolkit' )
			);

			return $tabs;
		}

		/**
		 * Add Settings Page
		 * @since 1.0
		 * @version 1.0
		 */
		public function add_settings_page() {

			add_submenu_page( 'options-general.php', __( 'Leaderboards', 'mycred-toolkit' ), __( 'Leaderboards', 'mycred-toolkit' ), 'export', 'bp-leaderboards', array( $this, 'settings_screen' ) );
		}

		/**
		 * Remove Settings Page from Menu
		 * @since 1.0
		 * @version 1.0
		 */
		public function admin_head() {

			remove_submenu_page( 'options-general.php', 'bp-leaderboards' );
		}

		/**
		 * Settings Screen
		 * @since 1.0
		 * @version 1.0.1
		 */
		public function settings_screen() {

			// We're saving our own options, until the WP Settings API is updated to work with Multisite.
			$form_action       = add_query_arg( 'page', 'bp-leaderboards', bp_get_admin_url( 'admin.php' ) );
			$point_types       = mycred_get_types();
			$settings          = bpmycredleaderboarderboard_settings();
			$leaderboard_types = mycred_get_bp_leaderboard_types();

			?>
<style type="text/css">
#mycred-point-types label { display: inline-block; margin-right: 12px; }
</style>
<div class="wrap">

	<?php if ( ! is_multisite() ) : ?>
	<h1><?php esc_html_e( 'BuddyPress Settings', 'mycred-toolkit' ); ?> <a href="https://mycred.me/store/mycred-bp-group-leaderboards/" class="page-title-action" target="_blank"><?php esc_html_e( 'Documentation', 'mycred-toolkit' ); ?></a></h1>

	<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Leaderboards', 'mycred-toolkit' ) ); ?></h2>
	<?php else : ?>
	<h1><?php esc_html_e( 'myCred BP Group Leaderboard Settings', 'mycred-toolkit' ); ?> <a href="https://mycred.me/store/mycred-bp-group-leaderboards/" class="page-title-action" target="_blank"><?php esc_html_e( 'Documentation', 'mycred-toolkit' ); ?></a></h1>
	<?php endif; ?>

	<form action="options.php" method="post">

		<?php settings_fields( 'bp-leaderboards' ); ?>

		<h3><?php esc_html_e( 'Default Setup', 'mycred-toolkit' ); ?></h3>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Point Types', 'mycred-toolkit' ); ?></th>
					<td>
						<p id="mycred-point-types"><?php mycred_types_select_from_checkboxes( 'mycred_bp_leaderboards[types][]', 'mycred-bp-group-leaderboard', $settings['types'] ); ?></p>
						<p class="description"><?php esc_html_e( 'Select the point type(s) to base the leaderboard on. If more then one type is selected, members will be able to select which point type to show in each group.', 'mycred-toolkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Leaderboard Size', 'mycred-toolkit' ); ?></th>
					<td>
						<input type="text" name="mycred_bp_leaderboards[size]" id="mycred-bp-group-leaderboard-size" value="<?php echo esc_attr( $settings['size'] ); ?>" />
						<p class="description">
						<?php 
						// Translators: %d is the maximum number of users to show in the leaderboards.
						printf( esc_html__( 'The number of users to show in the leaderboards by default. Maximum %d.', 'mycred-toolkit' ), esc_html( MYCRED_BP_GROUP_LEAD_MAX_SIZE ) );
						?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Append Current Member', 'mycred-toolkit' ); ?></th>
					<td>
						<p><label for="mycred-bp-group-leaderboard-current-yes"><input type="radio" name="mycred_bp_leaderboards[current]" id="mycred-bp-group-leaderboard-current-yes"<?php checked( $settings['current'], 1 ); ?> value="1" /> <?php esc_html_e( 'Yes', 'mycred-toolkit' ); ?></label><br /><label for="mycred-bp-group-leaderboard-current-no"><input type="radio" name="mycred_bp_leaderboards[current]" id="mycred-bp-group-leaderboard-current-no"<?php checked( $settings['current'], 0 ); ?> value="0" /> <?php esc_html_e( 'No', 'mycred-toolkit' ); ?></label></p>
						<p class="description"><?php esc_html_e( 'When a member views the leaderboard and they are not in the size set, do you want the user to be appended to the end of the leaderboard with their current position?', 'mycred-toolkit' ); ?></p>
					</td>
				</tr>
<?php

			if ( ! empty( $leaderboard_types ) ) {

				?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Leaderboard Type', 'mycred-toolkit' ); ?></th>
					<td>
<?php

				if ( ! array_key_exists( 'type', $settings ) ) {
					$settings['type'] = 'current';
				}

				?>
						<div id="bp-group-leaderboard-types">
<?php 
				foreach ( $leaderboard_types as $type_id => $type_label ) {
					

					?>
							<p>
								<label for="bp-leaderboard-type-<?php echo esc_attr( $type_id ); ?>" style="font-weight: normal;"><input type="radio" name="mycred_bp_leaderboards[type]" class="bp-leaderboard-type" id="bp-leaderboard-type-<?php echo esc_attr( $type_id ); ?>"<?php checked( $settings['type'], $type_id ); ?> value="<?php echo esc_attr( $type_id ); ?>" /> <?php echo esc_attr( $type_label ); ?></label>
								<?php do_action( "mycred_bp_leaderboard_{$type_id}_prefs_field", $settings['type'], $settings ); ?>
							</p>
<?php

				}

				?>
						</div>
					</td>
				</tr>
<?php

			}

			?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Group Override', 'mycred-toolkit' ); ?></th>
					<td>
						<p><label for="mycred-bp-group-leaderboard-override-no"><input type="radio" name="mycred_bp_leaderboards[override]" id="mycred-bp-group-leaderboard-override-no"<?php checked( $settings['override'], 1 ); ?> value="1" /> <?php esc_html_e( 'Enforce these default settings for all group leaderboards.', 'mycred-toolkit' ); ?></label><br />
						<label for="mycred-bp-group-leaderboard-override-yes"><input type="radio" name="mycred_bp_leaderboards[override]" id="mycred-bp-group-leaderboard-override-yes"<?php checked( $settings['override'], 0 ); ?> value="0" /> <?php esc_html_e( 'Allow group admins to change leaderboard setups for each group.', 'mycred-toolkit' ); ?></label></p>
					</td>
				</tr>
			</tbody>
		</table>

		<h3><?php esc_html_e( 'Group menu', 'mycred-toolkit' ); ?></h3>
		<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Menu Title', 'mycred-toolkit' ); ?></th>
					<td>
						<input type="text" name="mycred_bp_leaderboards[title]" id="mycred-bp-group-leaderboard-title" style="width: 50%;" value="<?php echo esc_attr( $settings['title'] ); ?>" />
						<p><?php esc_html_e( 'The group menu title.', 'mycred-toolkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Menu Slug', 'mycred-toolkit' ); ?></th>
					<td>
						<input type="text" name="mycred_bp_leaderboards[slug]" id="mycred-bp-group-leaderboard-slug" class="code" size="32" value="<?php echo esc_attr( $settings['slug'] ); ?>" />
						<p><?php esc_html_e( 'The group menu URL slug.', 'mycred-toolkit' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Menu Position', 'mycred-toolkit' ); ?></th>
					<td>
						<input type="text" name="mycred_bp_leaderboards[position]" id="mycred-bp-group-leaderboard-position" size="10" value="<?php echo esc_attr( $settings['position'] ); ?>" />
						<p><?php esc_html_e( 'The menu position.', 'mycred-toolkit' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<?php do_action( 'mycred_bp_leaderboard_prefs', $settings ); ?>

		<p class="submit">
			<input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'mycred-toolkit' ); ?>" />
		</p>
	</form>
</div>
<?php
		}

		/**
		 * Sanitize Settings
		 * @since 1.0
		 * @version 1.1
		 */
		public function sanitize_settings( $data ) {

			$settings             = bpmycredleaderboarderboard_settings();
			$new_data             = $settings;

			if ( array_key_exists( 'types', $data ) && ! empty( $data['types'] ) ) {

				$types             = array();
				foreach ( $data['types'] as $point_type ) {

					$point_type = sanitize_key( $point_type );
					if ( mycred_point_type_exists( $point_type ) ) {

						$types[] = $point_type;

					}

				}

				$new_data['types'] = $types;

			}

			$new_data['size']     = absint( $data['size'] );
			$new_data['current']  = ( isset( $data['current'] ) ) ? absint( $data['current'] ) : MYCRED_BP_GROUP_LEAD_MAX_SIZE;
			$new_data['type']     = ( isset( $data['type'] ) ) ? sanitize_key( $data['type'] ) : 'current';
			$new_data['override'] = ( isset( $data['override'] ) ) ? absint( $data['override'] ) : 0;

			if ( $new_data['type'] == 'custom' ) {
				$new_data['from']  = sanitize_text_field( $data['from'] );
				$new_data['until'] = sanitize_text_field( $data['until'] );
			} else {
				$new_data['from']  = '';
				$new_data['until'] = '';
			}

			$new_data['title']    = sanitize_text_field( $data['title'] );
			$new_data['slug']     = sanitize_title( $data['slug'] );
			$new_data['position'] = absint( $data['position'] );

			$point_types = mycred_get_types();
			if ( ! empty( $point_types ) ) {
				foreach ( $point_types as $point_type => $label ) {
					$this->delete_cached_leaderboards( $point_type );
				}
			}

			return apply_filters( 'mycred_bp_leaderboard_save_prefs', $new_data, $data, $settings );
		}

		/**
		 * Load Textdomain
		 * @since 1.0
		 * @version 1.0
		 */
		public function load_textdomain() {

			// Load Translation
			$locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );

			load_textdomain( $this->domain, WP_LANG_DIR . '/' . $this->slug . '/' . $this->domain . '-' . $locale . '.mo' );
			load_plugin_textdomain( $this->domain, false, dirname( $this->plugin ) . '/lang/' );
		}
	}
endif;

function bpmycredleaderboarderboards_plugin() {
	return myCRED_Toolkit_BP_Group_Leaderboards::instance();
}
bpmycredleaderboarderboards_plugin();
