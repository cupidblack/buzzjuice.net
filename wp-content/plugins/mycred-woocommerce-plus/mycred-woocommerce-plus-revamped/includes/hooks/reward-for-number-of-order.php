<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hook for number of order
 * @since 1.8
 * @version 1.0
 */
if ( ! class_exists( 'MWP_Hook_Number_Of_Order' ) ) :
	class MWP_Hook_Number_Of_Order extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'woocommerce_numbers_of_orders',
				'defaults' => array(
					'num_of_order' => array(),
					'creds'   	   => array(),
					'log'     	   => array()
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'woocommerce_order_status_changed', array( $this, 'mycred_woo_hook_rewards' ), 10, 4 );
		}

		/**
		 * Add points
		 * @since 1.0
		 * @version 1.0
		 */
		public function mycred_woo_hook_rewards($order_id, $old_status, $new_status, $_this)
		{
			global $woocommerce;
			// status other than givenin reward-setting. return here
			if( ! in_array( 'wc-'.$new_status, mycred_get_woocommerce_settings('reward')['status'] ) ) return;

			$order    = wc_get_order( $order_id );

			// Make sure user is not excluded
			$user_id  = ( version_compare( $woocommerce->version, '3.0', '>=' ) ) ? $order->get_user_id() : $order->user_id;

			if ( $this->core->exclude_user( $user_id ) ) return;
			
			$references = apply_filters( 'mycred_woocommerce_points_ref', 'woocommerce_number_of_order_reward', $order );
			$data       = array( 'ref_type' => 'post' );
			
			// Make sure this is unique
			if ( $this->core->has_entry( $references, $order_id, $user_id, $data, $this->mycred_type ) ) return;

			if ( 
				! empty( $this->prefs['creds'] ) && 
				! empty( $this->prefs['log'] ) && 
				! empty( $this->prefs['num_of_order'] ) 
			) {


				$threshold = array();

				foreach ( $this->prefs['creds'] as $key => $value ) {
					if ( wc_get_customer_order_count( $user_id ) == $this->prefs['num_of_order'][$key] ) {
						array_push( $threshold, $key );
					}
				}
				
				if ( ! empty( $threshold ) ) {
					$hook_index = end( $threshold );
					// Execute

					$this->core->add_creds(
						$references,
						$user_id,
						$this->prefs['creds'][$hook_index],
						$this->prefs['log'][$hook_index],
						$order_id,
						$data,
						$this->mycred_type
					);

				}
				
			}
			
		}
		/**
		 * Preference for Anniversary Hook
		 * @since 1.8
		 * @version 1.0
		 */
		public function preferences() {
			$prefs = $this->prefs;

			if ( is_array( $prefs['creds'] ) && count( $prefs['creds'] ) > 0 ) {
				$hooks = $this->number_of_order_arrange_data( $prefs );
				$this->number_of_order_reward_setting( $hooks, $this );
			}
			else {
				$default_data = array(
					array(
						'num_of_order' => 1,
						'creds'   => 10,
						'log'     => '%plural% for number of order'
					)
				);
				$this->number_of_order_reward_setting( $default_data, $this );
			}
		}

		/**
		 * Preference for Anniversary Hook
		 * @since 1.8
		 * @version 1.0
		 */
		public function number_of_order_reward_setting($data) {
			foreach ( $data as $hook ): ?>
				<div class="hook-instance">
					<div class="row">
						<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $this->field_id( 'num_of_order' ) ); ?>"><?php echo esc_html( 'No. of Orders' ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->name( $this->mycred_type, 'num_of_order' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'num_of_order' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $hook['num_of_order'] ) ); ?>" class="form-control" />
							</div>
						</div>
						<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->name( $this->mycred_type, 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" value="<?php echo esc_attr( $this->core->number( $hook['creds'] ) ); ?>" class="form-control" />
							</div>
						</div>
						<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
							<div class="form-group">
								<label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred' ); ?></label>
								<input type="text" name="<?php echo esc_attr( $this->name( $this->mycred_type, 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $hook['log'] ); ?>" class="form-control" />
								<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) ) ); ?></span>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 textright">
							<button class="button button-small mycred-woo-add-specific-hook" type="button">Add More</button>
							<button class="button button-small mycred-woo-remove-specific-hook" type="button">Remove</button>
						</div>
					</div>
				</div>
				<?php
			endforeach;
		}

	  	/**
	     * Sanitize Preferences
	     */
	  	public function sanitise_preferences( $data ) {

	  		$new_data = array();

	  		foreach ( $data as $data_key => $data_value ) {
	  			foreach ( $data_value as $key => $value) {
	  				if ( $data_key == 'creds' ) {
	  					$new_data[$data_key][$key] = ( !empty( $value ) ) ? floatval( $value ) : 10;
	  				}
	  				else if ( $data_key == 'log' ) {
	  					$new_data[$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for order range';
	  				}
	  				else if ( $data_key == 'num_of_order' ) {
	  					$new_data[$data_key][$key] = ( !empty( $value ) ) ? floatval( $value ) : 1;
	  				}
	  			}
	  		}
	  		return $new_data;
	  	}

	  	public function number_of_order_arrange_data( $data ){
	  		$hook_data = array();
	  		foreach ( $data['creds'] as $key => $value ) {
	  			$hook_data[$key]['creds'] = $data['creds'][$key];
	  			$hook_data[$key]['log']   = $data['log'][$key];
	  			$hook_data[$key]['num_of_order']   = $data['num_of_order'][$key];
	  		}
	  		return $hook_data;
	  	}

	  	public function name( $type, $attr ){
	  		$hook_prefs_key = 'mycred_pref_hooks';
	  		if ( $type != MYCRED_DEFAULT_TYPE_KEY ) {
	  			$hook_prefs_key = 'mycred_pref_hooks_'.$type;
	  		}
	  		return "{$hook_prefs_key}[hook_prefs][woocommerce_numbers_of_orders][{$attr}][]";
	  	}

	  }
	endif;
