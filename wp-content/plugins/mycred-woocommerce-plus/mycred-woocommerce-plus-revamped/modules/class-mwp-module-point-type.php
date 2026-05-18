<?php
if ( ! defined( 'myCRED_VERSION' ) ) exit;

/**
 * myCRED_PartialPayment_Pointtype_Module class
 * @since 1.8
 * @version 1.0
 */
if ( ! class_exists( 'myCRED_PartialPayment_Pointtype_Module' ) ) :
	class myCRED_PartialPayment_Pointtype_Module extends myCRED_Module {

		/**
		 * Construct
		 */
		public function __construct() {

			parent::__construct( 'myCRED_PartialPayment_Pointtype_Module', array(
				'module_name' => 'partial_payment_settings',
				'accordion'   => false,
				'register'    => false,
				'add_to_core' => false

			) );
		}

		/**
		 * Load
		 * @since 1.8
		 * @version 1.0
		 */
		public function load() {

			add_action( 'mycred_admin_init', array( $this, 'module_admin_init' ) );
		}

		/**
		 * Admin Init
		 * @since 1.8
		 * @version 1.0
		 */
		public function module_admin_init() {
			$available_types = mycred_get_types(true);

			foreach ($available_types as $type => $type_key ) {
				if( $type == MYCRED_DEFAULT_TYPE_KEY ) {
					add_action( 'mycred_type_prefs', array( $this, 'after_general_settings' ), 10 );
					add_filter( 'mycred_save_core_prefs',        array( $this, 'sanitize_extra_settings' ), 10, 3 );
				}
				add_action( 'mycred_type_prefs' . $type, array( $this, 'after_general_settings' ), 10 );
				add_filter( 'mycred_save_core_prefs' . $type,        array( $this, 'sanitize_extra_settings' ), 10, 3 );
			}

			
		}

		/**
		 * Settings
		 * @since 1.8
		 * @version 1.0
		 */
		public function after_general_settings( $mycred = NULL ) {
			
			$enable_pointtype = isset( $mycred->core->core['partial_payment_settings']['enable_pointtype'] ) ? $mycred->core->core['partial_payment_settings']['enable_pointtype']  : 0;
			$exchange_rate = ! empty( $mycred->core->core['partial_payment_settings']['exchange_rate'] ) ? $mycred->core->core['partial_payment_settings']['exchange_rate'] : 1;
			$mwp_min = ! empty( $mycred->core->core['partial_payment_settings']['mwp_min'] ) ? $mycred->core->core['partial_payment_settings']['mwp_min'] : 1;
			$mwp_max = ! empty( $mycred->core->core['partial_payment_settings']['mwp_max'] ) ? $mycred->core->core['partial_payment_settings']['mwp_max'] : 100;

			?>

			<div class="mycred-ui-accordion">
				<div class="mycred-ui-accordion-header">
					<h4 class="mycred-ui-accordion-header-title">
						<span class="dashicons dashicons-money-alt static mycred-ui-accordion-header-icon"></span>
						<label><?php esc_html_e( 'Partial Payment ( WooCommerce )', 'mycred-woocommerce-plus' ); ?></label>
					</h4>
					<div class="mycred-ui-accordion-header-actions hide-if-no-js">
						<button type="button" aria-expanded="true">
							<span class="mycred-ui-toggle-indicator" aria-hidden="true"></span>
						</button>
					</div>
				</div>

				<div class="body mycred-ui-accordion-body" style="display:none;">    
					<div class="row">
						<h2 style="margin-top: 20px !important; margin-bottom: 22px !important;">
							<?php echo esc_html__('Partial Payment Settings', 'mycred-woocommerce-plus'); ?>
						</h2>

						<!-- Enable Setting -->
						<div class="col-xs-12 col-sm-12 col-lg-12 col-md-12">
							<div class="form-group">
								<?php  
								mycred_create_toggle_field( 
									array(
										'id' => $mycred->field_id( array( 'partial_payment_settings' , 'enable_pointtype' ) ),
										'name' => $mycred->field_name(  array( 'partial_payment_settings' , 'enable_pointtype' ) ),
										'label' => __( 'Enable Point Type', 'mycred-woocommerce-plus' ),
										'after' => true
									), 
									$enable_pointtype,
									$enable_pointtype
								);
								?>
								<p><i><?php esc_html_e( 'Enable Point type to show on partial payment dropdown.', 'mycred-woocommerce-plus' );?></i></p>
							</div>
						</div>

						<!-- Exchange Rate Setting -->
						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $mycred->field_id( array( 'partial_payment_settings', 'exchange_rate' ) ) ); ?>">
									<?php esc_html_e('Exchange Rate', 'mycred-woocommerce-plus'); ?>
								</label>
								<input type="text" 
								name="<?php echo esc_attr( $mycred->field_name( array( 'partial_payment_settings', 'exchange_rate' ) ) ); ?>" 
								id="<?php echo esc_attr( $mycred->field_id( array( 'partial_payment_settings', 'exchange_rate' ) ) ); ?>" 
								class="form-control" 
								value="<?php echo $exchange_rate ?>" 
								min="1">
								<p>
									<i>
										<?php esc_html_e('How much is 1 point worth in your store currency?', 'mycred-woocommerce-plus'); ?>
									</i>
								</p>
							</div>
						</div>

						<!-- Minimum Setting -->
						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $mycred->field_id( array( 'partial_payment_settings', 'mwp_min' ) ) ); ?>">
									<?php esc_html_e('Minimum', 'mycred-woocommerce-plus'); ?>
								</label>
								<input type="number" 
								name="<?php echo esc_attr( $mycred->field_name( array( 'partial_payment_settings', 'mwp_min' ) ) ); ?>" 
								id="<?php echo esc_attr( $mycred->field_id( array( 'partial_payment_settings', 'mwp_min' ) ) ); ?>" 
								class="form-control" 
								value="<?php echo $mwp_min; ?>" 
								min="1">
								<p>
									<i>
										<?php esc_html_e('The minimum amount of points a user must pay.', 'mycred-woocommerce-plus'); ?>
									</i>
								</p>
							</div>
						</div>

						<!-- Maximum Setting -->
						<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $mycred->field_id( array( 'partial_payment_settings', 'mwp_max' ) ) ); ?>">
									<?php esc_html_e('Maximum', 'mycred-woocommerce-plus'); ?>
								</label>
								<input type="number" 
								name="<?php echo esc_attr( $mycred->field_name( array( 'partial_payment_settings', 'mwp_max' ) ) ); ?>" 
								id="<?php echo esc_attr( $mycred->field_id( array( 'partial_payment_settings', 'mwp_max' ) ) ); ?>" 
								class="form-control" 
								value="<?php echo $mwp_max; ?>" 
								min="1"
								max="100">
								<p>
									<i>
										<?php esc_html_e('The maximum percentage of the cart total a user can pay using points.', 'mycred-woocommerce-plus'); ?>
									</i>
								</p>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php
		}


		/**
		 * Sanitize Settings
		 * @since 1.8
		 * @version 1.0
		 */
		public function sanitize_extra_settings( $new_data, $data, $core ) {
			$new_data['partial_payment_settings']['enable_pointtype'] = ( isset( $data['partial_payment_settings']['enable_pointtype'] ) ) && $data['partial_payment_settings']['enable_pointtype'] == 'on' ? 1 : 0;
			$new_data['partial_payment_settings']['mwp_min'] = ( isset( $data['partial_payment_settings']['mwp_min'] ) ) ? $data['partial_payment_settings']['mwp_min'] : 1;
			$new_data['partial_payment_settings']['exchange_rate'] = ( isset( $data['partial_payment_settings']['exchange_rate'] ) ) ? $data['partial_payment_settings']['exchange_rate'] : 1;
			$new_data['partial_payment_settings']['mwp_max'] = ( isset( $data['partial_payment_settings']['mwp_max'] ) ) ? ( $data['partial_payment_settings']['mwp_max'] > 100 ? 100 : $data['partial_payment_settings']['mwp_max'] ) : 100;

			return $new_data;
		}

	}
endif;

/**
 * Load myCRED_PartialPayment_Pointtype_Module Module
 * @since 1.7
 * @version 2.0
 */
if ( ! function_exists( 'mycred_load_partial_payment_settings' ) ) :
	function mycred_load_partial_payment_settings( $modules, $point_types ) {

		$modules['solo']['partial_payment_settings'] = new myCRED_PartialPayment_Pointtype_Module();
		$modules['solo']['partial_payment_settings']->load();

		return $modules;

	}
endif;
add_filter( 'mycred_load_modules', 'mycred_load_partial_payment_settings', 80, 2 );