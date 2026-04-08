<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SureCart_Hook_Number_Of_Order' ) ) :
	class SureCart_Hook_Number_Of_Order extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'surecart_numbers_of_orders',
				'defaults' => array(
					'num_of_order' => 1,
					'creds'   => 10,
					'log'     => '%plural% for number of order'
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'surecart/purchase_created', array( $this, 'mycred_surecart_number_orders' ) , 100 , 1);
			
		}

		public function mycred_surecart_number_orders( $purchase ) {

		    $wp_user = $purchase->getWPUser();
		    if ( ! is_object( $wp_user ) || ! isset( $wp_user->ID ) ) {
		        return;
		    }

		    $user_id = $wp_user->ID;
		    $order_id = $purchase->initial_order ?? null;
		   
		    // Check user's total successful orders
		    $order_count = $this->get_surecart_customer_order_count($user_id);
		    if ( $order_count != $this->prefs['num_of_order'] ) {
		        return;
		    }

		    $references = 'surecart_numbers_of_orders';
		    $data = [ 'ref_type' => 'post' ];

		    // Prevent duplicate rewards for the same order
		    if ( $this->core->has_entry( $references, $order_id, $user_id, $data, $this->mycred_type ) ) {
		        return;
		    }

		    // Award points
		    $this->core->add_creds(
		        $references,
		        $user_id,
		        $this->prefs['creds'],
		        $this->prefs['log'],
		        $order_id,
		        $data,
		        $this->mycred_type
		    );

		}

		private function get_surecart_customer_order_count($user_id) {

		    try {
		        // Get customer IDs for the specified user
		        $user = \SureCart\Models\User::find($user_id);
		        if (!$user) {
		            return 0;
		        }
		        
		        $customer_ids = $user->customerIds();
		        if (empty($customer_ids)) {
		            return 0;
		        }

		        // Set up the query for orders
		        $query = [
		            'customer_ids' => array_values($customer_ids),
		            'status'      => ['paid'],
		            'page'        => 1,
		            'per_page'    => 100
		        ];

		        // Fetch orders using the query
		        $orders = \SureCart\Models\Order::where($query)->get();

		        // Return the count if orders exist
		        if (is_array($orders) || $orders instanceof Countable) {
		            return count($orders);
		        }

		        return 0;
		    } catch (\Exception $e) {
		        error_log('Error fetching SureCart orders: ' . $e->getMessage());
		        return 0;
		    }
		}

		/**
		 * Preference for Number of Order Hook
		 * @since 1.8
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;

			?>
			
			<div class="hook-instance">
				<div class="row">
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'num_of_order' ) ); ?>"><?php echo esc_html( 'No. of Orders' ); ?></label>
							<input type="number" name="<?php echo esc_attr( $this->field_name( 'num_of_order' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'num_of_order' ) ); ?>" min="0" value="<?php echo esc_attr( $this->core->number( $prefs['num_of_order'] ) ); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
							<input type="number" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" min="0" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
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

			$new_data['creds'] = ! empty( $data['creds'] ) ? (float)$data['creds'] : 10;
			$new_data['num_of_order'] = ! empty( $data['num_of_order'] ) ? abs( (int)$data['num_of_order'] ) : 0;
			$new_data['log'] = ! empty( $data['log'] ) ? wp_kses_post( $data['log'] ) : '%plural% for number of order';
			
			return $new_data;
		}

	}
endif;