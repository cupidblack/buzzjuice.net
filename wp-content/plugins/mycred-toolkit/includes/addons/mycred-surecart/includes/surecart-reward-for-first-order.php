<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SureCart_Hook_First_Order' ) ) :
	class SureCart_Hook_First_Order extends myCRED_Hook {

		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'surecart_first_order',
				'defaults' => array(
					'creds'   => 10,
					'log'     => '%plural% for first order'
				)
			), $hook_prefs, $type );

		}

		/**
		 * Run
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'surecart/purchase_created', array( $this, 'mycred_surecart_first_order' ) , 100 , 1);

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

		public function mycred_surecart_first_order( $purchase ) {

			// Retrieve the WordPress user
		    $wp_user = $purchase->getWPUser();

		    // Bail if no user is found
		    if ( empty( $wp_user ) || ! isset( $wp_user->ID ) ) {
		        return;
		    }

		    // Extract user ID
		    $user_id = $wp_user->ID;

		    // Check if the user has made any previous orders
		    if ( $this->get_surecart_customer_order_count( $user_id ) > 1 ) {

		    	return;
		    }

		    if ( $this->core->has_entry( 'surecart_first_order', $purchase->initial_order, $user_id, $data, $this->mycred_type ) ) return;

		    // Award points for the first order
		    $this->core->add_creds(
		        'surecart_first_order',       
		        $user_id,                     
		        $this->prefs['creds'], 
		        $this->prefs['log'],   
		        $purchase->initial_order,     
		        array( 'ref_type' => 'post' ),
		        $this->mycred_type         
		    );

		    

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
							<label for="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>"><?php echo esc_html( $this->core->plural() ); ?></label>
							<input type="number" name="<?php echo esc_attr( $this->field_name( 'creds' ) ); ?>" id="<?php echo esc_attr( $this->field_id( 'creds' ) ); ?>" min="0" value="<?php echo esc_attr( $this->core->number( $prefs['creds'] ) ); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
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
			$new_data['creds'] = ! empty( $data['creds'] ) ? (float) $data['creds'] : 0;
			$new_data['log'] = ! empty( $data['log'] ) ? sanitize_text_field( $data['log'] ) : '%plural% for first order';

			return $new_data;

		}

	}
endif;