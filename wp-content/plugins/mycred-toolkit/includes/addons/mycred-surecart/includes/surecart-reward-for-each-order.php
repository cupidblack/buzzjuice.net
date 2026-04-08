<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SureCart_Hook_Each_Order' ) ) :
	class SureCart_Hook_Each_Order extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'surecart_each_order',
				'defaults' => array(
					'reward_on_each' => 'fixed_rate',
					'creds'   => 10,
					'log'     => '%plural% for each order'
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'surecart/purchase_created', array( $this, 'mycred_surecart_new_purchase' ) , 100 , 1);

		}

		public function mycred_surecart_new_purchase( $purchase ) {


			 $wp_user = $purchase->getWPUser();

		    // Bail if no user is found
		    if ( empty( $wp_user ) || ! isset( $wp_user->ID ) ) {
		        return;
		    }

		    // Extract user ID
		    $user_id = $wp_user->ID;

		    $product_id = $purchase->product  ?? null;
		    $quantity = $purchase->quantity ?? 1;
		    $customer = $purchase->customer ?? null;
		    $order_id = $purchase->initial_order ?? null;
		    $data      = array( 'ref_type' => 'post' );

		    if ( ! $order_id ) {
		        return;
		    }

		    if ( $this->core->has_entry( 'surecart_each_order', $order_id, $user_id, $data, $this->mycred_type ) ) return;
		   

		    for ( $i = 0; $i < $quantity; $i++ ) {

		    	$this->core->add_creds(
				'surecart_each_order',
				$user_id,
				$this->prefs['creds'],
				$this->prefs['log'],
				$order_id,
				$data,
				$this->mycred_type
			);


		    }


		}

		
		/**
		 * Preferences
		 * @since 1.0
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;
			?>
			
			<div class="hook-instance">
				<div class="row">
					<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'reward_on_each' ) );?>"><?php esc_html_e( 'Reward Type', 'mycred-toolkit' );?></label> 
							<select name="<?php echo esc_attr( $this->field_name( 'reward_on_each' ) );?>" class="form-control" id="<?php echo esc_attr( $this->field_id( 'reward_on_each' ) ); ?>">
								<option value="fixed_rate"><?php echo esc_html( 'Fixed' ) ; ?></option>
							</select>
							<span class="description"><?php esc_html_e( 'Select the type of reward you want to give', 'mycred-toolkit' );?></span>
						</div>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
							<input type="number" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" min="0" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>"><?php esc_html_e( 'Log Template', 'mycred' ); ?></label>
							<input type="text" name="<?php echo esc_attr( $this->field_name( 'log' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'log' ) ); ?>" placeholder="<?php esc_attr_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) ) ); ?></span>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

	  	/**
	     * Sanitize Preferences
	     */
		public function sanitise_preferences( $data ) {

			$new_data = array();
			$new_data['reward_on_each'] = 'fixed_rate';
			$new_data['creds'] = ! empty( $data['creds'] ) ? (float) $data['creds'] : 0;
			$new_data['log'] = ! empty( $data['log'] ) ? sanitize_text_field( $data['log'] ) : '%plural% for each order';

			return $new_data;

		}

	}
endif;