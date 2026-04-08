<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MYCRED_Toolkit_GI_IMPORTER' )) {

	/**
	 * myCRED Gamipress Importer class
	 **/
	class MYCRED_Toolkit_GI_IMPORTER {

		/**
		 * Construct
		 **/
		public function __construct() {
			$this->mycred_gi_define_constants();
			$this->mycred_gi_init();
		}

		/**
		 * mycred gamipress check define path
		 **/
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}

		/**
		 * mycred gamipress define constants
		 **/
		private function mycred_gi_define_constants() {
			$this->define( 'MYCRED_GI_PREFIX', 'mycred_gi_' );
			$this->define( 'mycred_gi_slug', 'mycred-toolkit');
			$this->define( 'mycred_gi', __FILE__ );
			$this->define( 'mycred_gi_root_dir', plugin_dir_path(mycred_gi) );
			$this->define( 'mycred_gi_includes_dir', mycred_gi_root_dir . 'page/' );
		}

		/**
		 * mycred gamipress initialize
		 **/
		private function mycred_gi_init() {
			add_action('admin_enqueue_scripts', array( $this, 'mycred_gi_enqueue' ));
			add_action( 'admin_notices', array( $this, 'mycred_gi_required_plugin_notices' ) );
			add_action('mycred_after_core_prefs', array( $this, 'mycred_gi_gamipress_importer_page' ));
			add_action('wp_ajax_mycred_gi_import_data', array( $this, 'mycred_gi_import_data' ));
			add_action('wp_ajax_nopriv_mycred_gi_import_data', array( $this, 'mycred_gi_import_data' ));
		}

		/**
		 * Enqueue Style and Scripts
		 */
		public function mycred_gi_enqueue() {
			wp_enqueue_script( MYCRED_GI_PREFIX . 'custom_script', plugin_dir_url( __FILE__ ) . 'assets/js/custom.js', '', 1.0 );
			wp_enqueue_style( MYCRED_GI_PREFIX . 'stylesheet', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', '', 1.0 );
		}

		/**
		 * Returns the last import date
		 * @param $category
		 * @return bool|mixed|string|void
		 */
		public function mycred_gi_get_last_import( $category ) {
			$result = !empty(get_option($category)) ? get_option($category) : 'Not Imported Yet.';
			return $result;
		}

		public function mycred_gi_get_class( $category ) {
			return !empty(get_option($category)) ? 'gi-not-empty' : 'gi-empty';
		}
		/**
		 * render form
		 */
		public function mycred_gi_gamipress_importer_page() {
			?>
			<div class="mycred-ui-accordion">
				<div class="mycred-ui-accordion-header">
					<h4 class="mycred-ui-accordion-header-title">
						<span class="dashicons dashicons-database-import  mycred-ui-accordion-header-icon"></span>
						<label>GamiPress Importer</label>
					</h4>
					<div class="mycred-ui-accordion-header-actions hide-if-no-js">
						<button type="button" aria-expanded="true">
							<span class="mycred-ui-toggle-indicator" aria-hidden="true"></span>
						</button>
					</div>
				</div>
				<div class="body mycred-ui-accordion-body" style="display:none;">
					<div class="row">
						<div class="col-md-6">
							<div style="margin: 10px 0;">
								<button name="gi_import_types" id="gi_import_types" class="button button-primary gi_import_points_types gi_import_button" value="gi_import_points_types">
									<span class="gi_icon dashicons dashicons-star-filled"></span>
									<label class="mycred-gamipress-label">Gamipress Import Points Types</label>
								</button>
							</div>
							<?php echo 'Last Import: ' . $this->mycred_gi_get_last_import('gi_import_types'); ?>

							<div style="margin: 10px 0;">
								<button name="gi_import_points" id="gi_import_points" class="button button-primary gi_import_points gi_import_button" value="gi_import_points">
									<span class="gi_icon dashicons-before dashicons-gamipress"></span>
									<label class="mycred-gamipress-label">Import GamiPress Points</label>
								</button>
							</div>
							<?php echo 'Last Import: ' . $this->mycred_gi_get_last_import('gi_import_points'); ?>

							<div style="margin: 10px 0;">
								<button name="gi_import_badgs" id="gi_import_badgs" class="button button-primary gi_import_badgs gi_import_button" value="gi_import_badgs">
									<span class="gi_icon dashicons-before dashicons-awards"></span>
									<label class="mycred-gamipress-label">Gamipress Import Achievements</label>
								</button>
							</div>
							<?php echo 'Last Import: ' . $this->mycred_gi_get_last_import('gi_import_badgs'); ?>

							<div style="margin: 10px 0;">
								<button name="gi_import_ranks" id="gi_import_ranks" class="button button-primary gi_ranks gi_import_button" value="gi_import_ranks">
									<span class="gi_icon dashicons-before dashicons-rank"></span>
									<label class="mycred-gamipress-label">Gamipress Import Ranks</label>
								</button>
							</div>
							<?php echo 'Last Import: ' . $this->mycred_gi_get_last_import('gi_import_ranks'); ?>
						</div>
						<div class="col-md-6 gi-right-div">
							<div>
								<h3>Instructions:</h3>
								<p class="mycred-note">Please follow the steps same as mentioned below.</p>
								<strong>Step 1. Import Point Types</strong>
								<p class="<?php echo $this->mycred_gi_get_class('gi_import_types'); ?>">
									First thing you have to import all GamiPress Points Types.
								</p>
								<strong>Step 2. Import Points</strong>
								<p class="<?php echo $this->mycred_gi_get_class('gi_import_points'); ?>">
									Next you have to import all the points values from GamiPress to myCred.
								</p>
								<strong>Step 3. Import Achievements</strong>
								<p class="<?php echo $this->mycred_gi_get_class('gi_import_badgs'); ?>">
									Import all GamiPress Achievements.
								</p>
								<strong>Step 4. Import Ranks</strong>
								<p class="<?php echo $this->mycred_gi_get_class('gi_import_ranks'); ?>">
									Then, at last Import all GamiPress Ranks.
								</p>
								<p class="gi_message"></p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Returns gamipress meta value
		 * @param $log_id
		 * @param $meta_key
		 * @since 1.0
		 * @return array|object|null
		 */
		public function get_gp_log_meta( $log_id, $meta_key ) {
			global $wpdb;
			$table_name = $wpdb->prefix . 'gamipress_logs_meta';
			$results = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_value FROM $table_name WHERE log_id = %d AND meta_key = %s", $log_id, $meta_key
				)
			);

			if ( !empty( $results ) ) {
				return $results;

			}
			return false;
		}

		/**
		 * Checks if log already exits or not
		 * @param $ref
		 * @param $user_id
		 * @param $creds
		 * @param $entry
		 * @since 1.0
		 */
		public function mycred_gi_log_check( $ref, $user_id, $creds, $ctype, $entry ) {
			global $wpdb;
			$mycred = mycred( 'mytype' );
			$mycred_logs = mycred()->log_table;
			$result = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT
                        l.ref, l.user_id, l.creds, l.entry
                        FROM $mycred_logs as l
                        WHERE
                        ref = %s AND user_id = %d AND creds = %d AND ctype = %s AND entry = %s", $ref, $user_id, $creds, $ctype, $entry
					)
			);

			if (is_array($result) and !empty($result)) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Checks and replaces the hook from GamiPress to myCRED
		 * @param $hook
		 */
		public function mycred_gi_replace_hooks( $hook ) {
			//Gathering myCRED hooks
			//$mycred_references = mycred_get_all_references();

			$references = array(
				'gamipress_register'                    => 'registration',
				'gamipress_login'                       => 'logging_in',
				'gamipress_new_comment'                 => 'approved_comment',
				'gamipress_specific_new_comment'        => 'gamipress_specific_new_comment',
				'gamipress_user_post_comment'           => 'gamipress_user_post_comment',
				'gamipress_user_specific_post_comment'  => 'gamipress_user_specific_post_comment',
				'gamipress_spam_comment'                => 'spam_comment',
				'gamipress_specific_spam_comment'       => 'gamipress_specific_spam_comment',
				'gamipress_publish_post'                => 'publishing_content',
				'gamipress_delete_post'                 => 'gamipress_delete_post',
				'gamipress_publish_page'                => 'publishing_content',
				'gamipress_delete_page'                 => 'gamipress_delete_page',
				'gamipress_add_role'                    => 'gamipress_add_role',
				'gamipress_add_specific_role'           => 'gamipress_add_specific_role',
				'gamipress_set_role'                    => 'gamipress_set_role',
				'gamipress_set_specific_role'           => 'gamipress_set_specific_role',
				'gamipress_remove_role'                 => 'gamipress_remove_role',
				'gamipress_remove_specific_role'        => 'gamipress_remove_specific_role',
				'gamipress_site_visit'                  => 'site_visit',
				'gamipress_post_visit'                  => 'view_content',
				'gamipress_specific_post_visit'         => 'gamipress_specific_post_visit',
				'gamipress_user_post_visit'             => 'gamipress_user_post_visit',
				'gamipress_user_specific_post_visit'    => 'gamipress_user_specific_post_visit',
				'specific-achievement'                  => 'specific-achievement',
				'any-achievement'                       => 'badge_reward',
				'all-achievements'                      => 'all-achievements',
				'earn-points'                           => 'earn-points',
				'points-balance'                        => 'points-balance',
				'gamipress_expend_points'               => 'gamipress_expend_points',
				'earn-rank'                             => 'earn-rank',
			);

			foreach ($references as $key => $value) {

				if ( $hook == $key ) {
					return $references[ $key ];
				}
			}
			if ($hook == 'gamipress_award_points') {
				return 'manual';
			} else {
				return $hook;
			}
		}

		/**
		 * Check if rank already exits
		 * @param $post_title pass post title
		 * @param $post_type pass post type
		 * @return string|null
		 */
		public function mycred_gi_rank_exists( $post_title, $post_type ) {
			global $wpdb;
			$results = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s", $post_title, $post_type
					)
			);
			return $results;
		}

		/**
		 * mycred_gi_import_data
		 **/
		public function mycred_gi_import_data() {
			if (isset($_REQUEST['check']) && $_REQUEST['check'] == 'gi_import_types') {
				$points_types = gamipress_get_points_types();
				if (empty($points_types)) {
					echo 'No Point Type Found';
					die;
				} else {
					// First, turn myCRED points types to our points types
					foreach ($points_types as $key => $data) {
						// Setup points type vars
						$singular = mycred_get_point_type_name($data['singular_name'], true);
						// If not exists, register as a new points type
						if (!empty($singular)) {
							$types[ $key ] = sanitize_text_field($data['plural_name']);

						} else {
							$points_type_id = $data['ID'];
						}
					}
					$types['mycred_default'] = 'myCRED';
					mycred_update_option('mycred_types', $types);
					update_option('gi_import_types', date('M d, Y'));
					echo 'Point Type Successfully Imported.';
					die;
				}
			}

			//Import Gamipress Points
			if (isset($_REQUEST['check']) && $_REQUEST['check'] == 'gi_import_points') {
				{
					$mycred = mycred('mytype');
					$mycred_log_table = $mycred->log_table;
					global $wpdb;
					$gp_logs = $wpdb->prefix . 'gamipress_logs';
					$gp_logs_meta = $wpdb->prefix . 'gamipress_logs_meta';
					$mycred_logs = mycred()->log_table;

					//Getting GamiPress logs
					$result = $wpdb->get_results(
						"
                        SELECT l.log_id, l.title, l.user_id, l.trigger_type,l.date FROM $gp_logs as l
                        ", ARRAY_A
					);
					//Importing Creds from GamiPress to MyCreds
				foreach ($result as $key) {
					$point_type = MYCRED_DEFAULT_TYPE_KEY;
					$mycred = mycred($point_type);
					$log_id = $key['log_id'];
					$ref = $key['trigger_type'];

					$ctype = !$this->get_gp_log_meta($log_id, '_gamipress_points_type') ? MYCRED_DEFAULT_TYPE_KEY : $this->get_gp_log_meta($log_id, '_gamipress_points_type');

					$user_id = $key['user_id'];

					$creds = !$this->get_gp_log_meta($log_id, '_gamipress_points') ? 1 : $this->get_gp_log_meta($log_id, '_gamipress_points');


					$entry = $key['title'];
					$ref = $this->mycred_gi_replace_hooks($ref);
					$exists = $this->mycred_gi_log_check($ref, $user_id, $creds, $ctype, $entry);

					if ($exists == false) {
						$mycred->add_creds(
							$ref,
							$user_id,
							$creds,
							$entry,
							'',
							'',
							$ctype
						);
					}
					continue;
				}
					update_option('gi_import_points', date('M d, Y'));
					echo 'GamiPress Points Successfully Imported.';
					die;
				}
			}

			if (isset($_REQUEST['check']) && $_REQUEST['check'] == 'gi_import_badgs') {
				$achievements_types = gamipress_get_achievement_types();
				$achievements = gamipress_get_achievements();
				if (empty($achievements)) {
					echo 'GamiPress No Achievement Found.';
					die;
				}
				foreach ($achievements_types as $key => $data) {
					$all_achievements = get_posts(array(
						'post_type' => $key,
						'post_status' => 'publish',
						'posts_per_page' => -1
					));
					if (!empty($all_achievements)) {
						/* $is_already_mycred_badge= ""; */
						foreach ($all_achievements as $achievement) {
							$post = get_post($achievement->ID);
							$is_already_mycred_badge = get_page_by_title($post->post_title, OBJECT, 'mycred_badge');

							if (empty($is_already_mycred_badge)) {
								// Create post object
								$my_post = array(
									'post_title' => wp_strip_all_tags($post->post_title),
									'post_type' => 'mycred_badge',
									'post_status' => wp_strip_all_tags($post->post_status),
									'post_author' => wp_strip_all_tags($post->post_author)
								);

								// Insert the post into the database
								$my_post_id = wp_insert_post($my_post);
								$postmeta = get_post_meta($post->ID);
								// Get all steps
								$all_step = get_posts(array(
									'post_parent' => $post->ID,
									'post_type' => 'step',
									'post_status' => 'publish',
									'posts_per_page' => -1
								));
								if (!empty($all_step)) {
									foreach ($all_step as $step) {
										$stepmeta = get_post_meta($step->ID);
										$requires_array = array(
											'type' => $postmeta['_gamipress_points_type'][0],
											'reference' => $stepmeta['_gamipress_trigger_type'][0],
											'amount' => $stepmeta['_gamipress_limit'][0],
											'by' => $stepmeta['_gamipress_limit_type'][0],
											'specific' => '',
										);

										$meta_value = array(
											'attachment_id' => $postmeta['_thumbnail_id'][0],
											'image_url' => '',
											'label' => '',
											'compare' => 'AND',
											'requires' => array( $requires_array ),
											'reward' => array(
												'type' => $postmeta['_gamipress_points_type'][0],
												'log' => $step->post_title,
												'amount' => $postmeta['_gamipress_points'][0],
											),
										);
										update_post_meta($my_post_id, 'main_image', '');
										update_post_meta($my_post_id, 'manual_badge', 0);
										$get_post_meta = get_post_meta($my_post_id, 'badge_prefs', true);

										if (!empty ($get_post_meta)) {
											array_push($get_post_meta, $meta_value);
											update_post_meta($my_post_id, 'badge_prefs', $get_post_meta);
										} else {
											update_post_meta($my_post_id, 'badge_prefs', array( $meta_value ));
										}
									}
								}
							}
						}
					}
				}
				update_option('gi_import_badgs', date('M d, Y'));
				echo 'GamiPress achievements has been migrated successfully.';
				die;
			}

			if (isset($_REQUEST['check']) && $_REQUEST['check'] == 'gi_import_ranks') {
				$ranks_types = gamipress_get_rank_types();
				$ranks = gamipress_get_ranks();
				if (empty($ranks)) {
					echo 'GamiPress No Ranks Found.';
					die;
				}
				foreach ($ranks_types as $key => $value) {
					global $mycred_ranks;
					$post_body = array(
						'post_type' => $key,
						'post_status' => 'publish',
						'posts_per_page' => -1
					);
					$posts = get_posts($post_body);
					foreach ($posts as $post) {
						$min_points = get_post_meta($post->ID, '_gamipress_points_to_unlock', true);
						$points_type = get_post_meta($post->ID, '_gamipress_points_type_to_unlock', true);
						$thumbnail_id = get_post_meta( $post->ID, '_thumbnail_id', true);
						$result = $this->mycred_gi_rank_exists($post->post_title, 'mycred_rank');
						if (is_null($result)) {

							$data = array(
								'post_title' => $post->post_title,
								'post_type' => MYCRED_RANK_KEY,
								'post_status' => $post->post_status,
								'post_author' => $post->post_author
							);
							$post_id = wp_insert_post($data);
							mycred_update_post_meta( $post_id, 'mycred_rank_min', $min_points );
							mycred_update_post_meta( $post_id, 'mycred_rank_max', 9999999 );
							mycred_update_post_meta( $post_id, 'ctype', MYCRED_DEFAULT_TYPE_KEY );
							mycred_update_post_meta( $post_id, '_thumbnail_id', $thumbnail_id );
							$mycred_ranks = 1;
							mycred_assign_ranks();
						}
					}
				}
				update_option('gi_import_ranks', date('M d, Y'));
				echo 'GamiPress Ranks has been migrated successfully.';
				die;
			}
		}


		/**
		 * mycred gamipress required plugin notices
		 **/
		public function mycred_gi_required_plugin_notices() {

			$msg = __( 'need to be active and installed to use myCred gamipress importer plugin.', 'mycred-toolkit' );

			if ( !is_plugin_active('mycred/mycred.php') ) {
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/mycred/">%1$s</a> %2$s</p></div>', esc_html_e( 'myCred', 'mycred-toolkit' ), esc_html( $msg ) );
			}
			if ( !is_plugin_active('gamipress/gamipress.php') ) {
				$gamipress_msg = __( ' need to be active and installed to use myCred gamipress importer plugin.', 'mycred-toolkit' );
				printf( '<div class="notice notice-error"><p><a href="https://wordpress.org/plugins/gamipress/">%1$s</a> %2$s</p></div>', esc_html_e( 'Gamipress', 'mycred-toolkit' ), esc_html( $gamipress_msg ) );
			}
		}
	}
	//end class
}

new MYCRED_Toolkit_GI_IMPORTER();
?>
