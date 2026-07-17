<?php

include_once ABSPATH . 'wp-admin/includes/plugin.php' ;


//If path not defined don't enter
if (!defined('ABSPATH')) {
	exit;
}

//Declaring Hook
if (!function_exists('spi_complete_purchase')) {
	function spi_complete_purchase( $installed ) {
		$installed['complete_pay'] = array(
			'title'       => __( 'Simple Pay: Completing a Purchase', 'mycred-toolkit' ),
			'description' => __( 'Simple Pay: Get points on completing a purchase.', 'mycred-toolkit' ),
			'callback'    => array( 'SPI_SimplePayCompletePurchase' )
		);
		return $installed;
	}
}

//Initializing Hook
add_filter( 'mycred_setup_hooks', 'spi_complete_purchase' );

//Enqueue JS
if (!function_exists('spi_load_js')) {
	function spi_load_js( $hook ) {
		wp_enqueue_script( 'my_custom_script', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', array(), '1.0' );
	}
}
add_action( 'admin_enqueue_scripts', 'spi_load_js' );

if (!is_plugin_active('mycred/mycred.php')) {   
	function sample_mycred_toolkit_admin_notice__error() {
		$class = 'notice notice-error';
		$message = __( 'Install and Active', 'mycred-toolkit' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) . ' <a href="https://wordpress.org/plugins/mycred/" target="_blank">myCred</a>' );
	}
	add_action( 'admin_notices', 'sample_mycred_toolkit_admin_notice__error' );
} elseif (!is_plugin_active('stripe/stripe-checkout.php')) {
	function sample_toolkit_admin_notice__error() {
		$class = 'notice notice-error';
		$message = __( 'Install and Active', 'mycred-toolkit' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) . ' <a href="https://wordpress.org/plugins/stripe/" target="_blank">WP Simple Pay</a>' );
	}
	add_action( 'admin_notices', 'sample_toolkit_admin_notice__error' );
} else {
//Load Plugin
add_action('plugins_loaded', function () {
		if (!class_exists('SPI_SimplePayCompletePurchase') ) :
			class SPI_SimplePayCompletePurchase extends myCRED_Hook {
			
				/**
				 * Construct
				 * @param $hook_prefs
				 * @param string $type
				 */
				function __construct( $hook_prefs, $type = 'mycred_default' ) {
					parent::__construct(array(
						'id' => 'complete_pay',
						'defaults' => array(
							'creds' => array(),
							'log' => array(),
							'limit' => array(),
							'form' => array()
						)
					), $hook_prefs, $type);
				}

				/**
				 * Run
				 * @since 0.1
				 * @version 1.1
				 */
				public function run() {

					// WordPress
					add_action('simpay_charge_created', array( $this, 'spi_completed_purchase' ), 10, 2);
				}
				public function spi_get_user_limit( $limit, $user_id, $form_id ) {

					$limit_period = explode( '/', $limit);
					$time = $limit_period[0]; //
					$period = $limit_period[1]; // d,m,w,t
					$date_to_check = ''; // no limit
					if ( $period == 'm' ) {
					$date_to_check = 'thismonth';
					} else if ( $period == 'w' ) {
					$date_to_check = 'thisweek';
					} else if ( $period == 'd' ) {
					$date_to_check = 'today';
					} else if ( $period == 't' ) {
					$date_to_check = 'total';
					} else { // when no limit set
					return true;
					}

					$args = array(
						'ref' => array(
							'ids' => 'complete_pay',
							'compare' => '='
						),
						'user_id'   => $user_id,
						'date'     => $date_to_check,
						'data'     => $form_id
					);

					$log  = new myCRED_Query_Log( $args );

					$used_limit = $log->num_rows;

					if ( $used_limit >= $time) {
					return false;
					}

					return true;
				}

				/**
				 * Login Hook
				 * @since 0.1
				 * @version 1.3
				 */
				public function spi_completed_purchase( $charge, $user_login = '' ) {
					$selected_forms = $this->prefs['form'];

					$count = count($this->prefs['creds']);

					$user_ID = get_current_user_id();

					$submitted_form = $charge->metadata->simpay_form_id;

					if (
					in_array('all', $this->prefs['form'])
					) {
						for ($i = 0; $i < $count; $i++) {
							// Check for exclusion
							if ($this->prefs['form'][ $i ] == 'all') {

								if ($this->core->exclude_user($user_ID)) {
return;
								}

								$response = $this->spi_get_user_limit($this->prefs['limit'][ $i ], $user_ID, $this->prefs['form'][ $i ]);

								if (
								$response == true
								) {
									$this->core->add_creds(
										'complete_pay',
										$user_ID,
										$this->prefs['creds'][ $i ],
										$this->prefs['log'][ $i ],
										'',
										$this->prefs['form'][ $i ],
										$this->mycred_type
									);
								} else {
									return;
								}


							}

						}
					}

					for ($i = 0; $i < $count; $i++) {
						// Check for exclusion
						if ($this->prefs['form'][ $i ] == $submitted_form) {

							if ($this->core->exclude_user($user_ID)) {
return;
							}

							$response = $this->spi_get_user_limit($this->prefs['limit'][ $i ], $user_ID, $this->prefs['form'][ $i ]);

							if (
								$response == true
							) {
								$this->core->add_creds(
									'complete_pay',
									$user_ID,
									$this->prefs['creds'][ $i ],
									$this->prefs['log'][ $i ],
									'',
									$this->prefs['form'][ $i ],
									$this->mycred_type
								);
							} else {
								return;
							}


						}

					}
				}


				/**
				 * @param $data
				 * @return array
				 */
				public function spi_arrange_data( $data ) {
					$hook_data = array();
					foreach ($data['form'] as $key => $value) {
						$hook_data[ $key ]['creds'] = $data['creds'][ $key ];
						$hook_data[ $key ]['log'] = $data['log'][ $key ];
						$hook_data[ $key ]['limit'] = $data['limit'][ $key ];
						$hook_data[ $key ]['form'] = $value;
					}
					return $hook_data;
				}


				/**
				 * @param $type
				 * @param $attr
				 * @return string
				 */
				function spi_field_name( $type, $attr ) {

					$hook_prefs_key = 'mycred_pref_hooks';

					if ( $type != MYCRED_DEFAULT_TYPE_KEY ) {
						$hook_prefs_key = 'mycred_pref_hooks_' . $type;
					}

					return "{$hook_prefs_key}[hook_prefs][complete_pay][{$attr}][]";
				}

				public function simpay_get_form_list_options() {
		$options = array();

					if ( empty( $options ) ) {
								$forms = get_posts(
						array(
							'post_type'      => 'simple-pay',
							'posts_per_page' => -1,
							'fields'         => 'ids',
						)
								);

						foreach ( $forms as $form_id ) {
						$options[ $form_id ] = get_the_title( $form_id );
						};
					}

		return $options;
				}


				/**
				 * @param $data
				 * @param $obj
				 */
				public function spi_hook_setting( $data, $obj ) {


					foreach ($data as $hook => $value) {
  
						$prefs = $this->prefs;
						global $wpdb;
						$table_name = $wpdb->prefix . 'posts';
						$results = $wpdb->get_results(
						"SELECT $table_name.post_title, $table_name.ID FROM $table_name WHERE $table_name.post_type = 'simple-pay' AND $table_name.post_status = 'publish'"
						);

						$get_all_forms = $this->simpay_get_form_list_options();


						?>
					<div class="hook-instance">
						<div class="row">
							<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
								<div class="form-group">
									<label for="<?php echo $obj->field_id( 'creds' ); ?>"><?php echo $obj->core->plural(); ?></label>
									<input type="text" name="<?php echo $obj->field_name( 'creds' ) . '[]'; ?>" id="<?php echo $obj->field_id( 'creds' ); ?>" value="<?php echo $obj->core->number( $data[ $hook ]['creds'] ); ?>" class="form-control" />
								</div>
							</div>
							<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
								<div class="form-group">
									<label for="<?php echo $obj->field_id( 'limit' ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
									<?php
									$limit_name = $this->spi_field_name($obj->mycred_type, 'limit');
									echo $obj->hook_limit_setting( $limit_name, $obj->field_id( 'limit' ), esc_attr( $data[ $hook ]['limit'] ) );
									?>
								</div>
							</div>
							<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
								<div class="form-group">
									<label for="<?php echo $obj->field_id( '' ); ?>"><?php esc_html_e( 'Select Form', 'mycred-toolkit' ); ?></label>
									<select class="form-control" name="<?php echo $obj->field_name('form') . '[]'; ?>" id="<?php echo $obj->field_id( 'form' ); ?>">
										<option value="">--Select Form--</option>
										<option selected value="all">All Forms</option>
										<?php

										foreach ($get_all_forms as $key => $value) {

											if ($key ==  $data[ $hook ]['form']) {

											 echo '<option selected="selected"  value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';

											} elseif (!in_array($key, $this->prefs['form'])) {

													echo '<option value="' . esc_attr( $key ) . '">' . esc_attr( $value ) . '</option>';
											}

										}
									
										?>
									</select>
								</div>
							</div> 
							<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
								<div class="form-group">
									<label for="<?php echo $obj->field_id( 'log' ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
									<input type="text" name="<?php echo $obj->field_name( 'log' ) . '[]'; ?>" id="<?php echo $obj->field_id( 'log' ); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $data[ $hook ]['log'] ); ?>" class="form-control" />
									<span class="description"><?php echo $obj->available_template_tags( array( 'general' ) ); ?></span>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
								<div class="form-group specific-hook-actions textright">
									<button class="button button-small mycred-add-spi" type="button">Add More</button>
									<button class="button button-small mycred-remove-spi" type="button">Remove</button>
								</div>
							</div>
						</div>
					</div>
						<?php
					}
				}


				/**
				 * Preference for Login Hook
				 * @since 0.1
				 * @version 1.2
				 */
				public function preferences() {
					$pref = $this->prefs;
					if (count($pref['form']) > 0) {
						$hook = $this->spi_arrange_data( $pref );
						$this->spi_hook_setting( $hook, $this );
					} else {
						$default_data = array(
							array(
								'creds'        =>         '10',
								'log'          =>         '%plural% for completing a purchase',
								'limit'        =>         '0',
								'form'         =>         'Select Form'
							)
						);
						$this->spi_hook_setting( $default_data, $this );
					}
				}

				/**
				 * Sanitise Preferences
				 * @since 1.6
				 * @version 1.0
				 */
				function sanitise_preferences( $data ) {
					$length = count($data['limit']);
					for ($i = 0; $i <= $length; $i++) {
						if (isset($data['limit'][ $i ]) && isset($data['limit_by'][ $i ])) {
							$limit = sanitize_text_field( $data['limit'][ $i ] );
							if ($limit == '') {
$limit = 0;
							}
							$data['limit'][ $i ] = $limit . '/' . $data['limit_by'][ $i ];
							unset($data['limit_by'][ $i ]);
						}
					}
					return $data;
				}
			}
			endif;
	});
}
