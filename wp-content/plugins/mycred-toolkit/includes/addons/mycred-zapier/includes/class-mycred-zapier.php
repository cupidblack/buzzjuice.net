<?php
add_filter('mycred_load_modules', 'mycred_load_zapier_module', 10, 2);
/**
 * Load zapier module
 * @param array $modules
 * @param array $point_types
 * @return \myCred_Zapier_module
 */
if (!function_exists('mycred_load_zapier_module')) :
	function mycred_load_zapier_module( $modules, $point_types ) {
		$modules['solo']['zapier'] = new myCred_Zapier_module();
		$modules['solo']['zapier']->load();

		return $modules;
	}

endif;
if ( !class_exists( 'myCred_Zapier_module' ) ) :
	class myCred_Zapier_module extends myCRED_Module {

		/**
		 * Constructor
		 */
		public function __construct( $point_type = MYCRED_DEFAULT_TYPE_KEY ) {

			$args = array(
				'module_name' => 'zapier',
				'register' => false,
				'add_to_core' => true,
				'defaults' => array(
					'enable_zapier_earn_points' => 0,
					'earn_points_webhook_url' => '',
					'enable_zapier_deduct_points' => 0,
					'deduct_points_webhook_url' => ''
				)
			);

			parent::__construct('myCred_Zapier_module', $args, $point_type);
		}

		public function module_init() {

			add_action( 'mycred_rank_promoted', array( $this, 'rank_promoted' ), 10, 3 );
			add_action( 'mycred_rank_demoted', array( $this, 'rank_demoted' ), 10, 3 );
			add_action( 'mycred_after_badge_assign', array( $this, 'badge_earned' ), 10, 3 );
			add_action( 'mycred_zapier_clean_logs', array( $this, 'delete_old_logs' ) );

			if ( wp_next_scheduled( 'mycred_zapier_clean_logs' ) === false ) {
			wp_schedule_event( time(), 'daily', 'mycred_zapier_clean_logs' );
			}
		}

		public function module_admin_init() {

			add_action('admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), $this->menu_pos);
		}

		public function enqueue_scripts() {
		
			wp_enqueue_script(
				'mycred-zapier-settings', MYCRED_ZAP_URL . '/assets/js/zapier-settings-scripts.js', array( 'jquery', 'mycred-mustache' ), myCRED_ZAPIER_VERSION
			);

			$current_user = wp_get_current_user();
			$array_opts = array(
				'user' => $current_user->user_nicename,
				'pass' => $current_user->user_pass
			);
			wp_localize_script( 'mycred-zapier-settings', 'MYCRED_ZAPIER', $array_opts );
		}

		public function after_general_settings( $mycred = null ) {

			$settings = $this->zapier;
			if (!isset($settings)) :
				$prefs = $this->default_prefs;
			else :
				$prefs = mycred_apply_defaults($this->default_prefs, $this->zapier);
			endif;
		
			?>
		<style type="text/css">
			.mycred-zapier-form-control {
				max-width: 60%;
			}
			#generate-zapier-api-key {
				height: 42px;
			}
		</style>
		<div class="mycred-ui-accordion">
			<div class="mycred-ui-accordion-header">
				<h4 class="mycred-ui-accordion-header-title">
					<span class="dashicons dashicons-admin-plugins static mycred-ui-accordion-header-icon"></span><?php esc_html_e('Zapier', 'mycred-toolkit'); ?>
				</h4>
				<div class="mycred-ui-accordion-header-actions hide-if-no-js">
					<button type="button" aria-expanded="true">
						<span class="mycred-ui-toggle-indicator" aria-hidden="true"></span>
					</button>
				</div>
			</div>
			<div class="body mycred-ui-accordion-body" style="display:none;">
				<h3><?php esc_html_e('Zapier Settings', 'mycred-toolkit'); ?></h3>
				<div class="row">
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
						<label for="site-url">Website URL</label>
						<p id="site-url">
							<?php echo esc_url( get_site_url() ); ?>
						</p>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
						<label for="<?php echo $this->field_id( 'mycred_zapier_api_key' ); ?>">API Key</label>
						<div class="h2">
							<input type="text" name="<?php echo $this->field_name( 'mycred_zapier_api_key' ); ?>" id="<?php echo $this->field_id( 'mycred_zapier_api_key' ); ?>" value="<?php echo !array_key_exists( 'zapier', $this->core->core ) ? '' : $this->core->core['zapier']['mycred_zapier_api_key']; ?>" class="mycred-zapier-form-control form-control">
							<input type="button" id="generate-zapier-api-key" class="button button-large button-primary" value="Click to Generate">
						</div>
					</div>
				</div>
				<h3><?php esc_html_e('Zapier Triggers', 'mycred-toolkit'); ?></h3>
				<div>
					<span class="dashicons dashicons-saved"></span> When User Earn Point(s)
				</div>
				<div>
					<span class="dashicons dashicons-saved"></span> When User lost Point(s)
				</div>
				<?php if (class_exists('myCRED_Badge_Module')) : ?>
				<div>
					<span class="dashicons dashicons-saved"></span> When User Earn Badge
				</div>
			   <?php endif; ?>
			   <?php if (class_exists('myCRED_Ranks_Module')) : ?>
				<div>
					<span class="dashicons dashicons-saved"></span> When User Earns Rank
				</div>
				<div>
					<span class="dashicons dashicons-saved"></span> When User Losts Rank
				</div>
			   <?php endif; ?>
			</div>
		</div>

		<?php
		}


		public function sanitize_extra_settings( $new_data, $data, $core ) {
		
			$new_data['zapier']['mycred_zapier_api_key'] = $data['zapier']['mycred_zapier_api_key'];
		
			return $new_data;
		}

		public function rank_promoted( $user_id, $rank_id, $rank_ids ) {

			mycred_zapier_insert_log( $user_id, 'rank_promoted', $rank_id );
		}

		public function rank_demoted( $user_id, $rank_id, $rank_ids ) {

			mycred_zapier_insert_log( $user_id, 'rank_demoted', $rank_id );
		}

		public function badge_earned( $user_id, $badge_id, $new_level ) {

			mycred_zapier_insert_log( $user_id, 'badge_earned', $badge_id );
		}

		public function delete_old_logs() {

			// The maximum age a log entry can have in seconds
			// maximum age 1 week
			$max_age   = ( 1 * WEEK_IN_SECONDS );
			$now       = current_time( 'timestamp' );

			// Times are stored as unix timestamps so we just deduct the seconds from now
			$timestamp = $now - $max_age;

			global $wpdb;

			$tbl_zapier = $wpdb->prefix . 'mycred_zapier';

			// Delete entries that are older than our $timestamp
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$tbl_zapier} WHERE created_time < %d;", $timestamp ) );
		}
	}
endif;