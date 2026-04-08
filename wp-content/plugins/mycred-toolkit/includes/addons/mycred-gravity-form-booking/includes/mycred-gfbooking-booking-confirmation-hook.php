<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * MyCRED_Gfbooking_Booking_Confirmation_Hook class
 * Creds for booking successful events updates
 */
if ( ! class_exists( 'MyCRED_Gfbooking_Booking_Confirmation_Hook' ) ) :
	class MyCRED_Gfbooking_Booking_Confirmation_Hook extends myCRED_Hook {

		/**
		 * Construct
		 */
		public function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'booking_confirmation',
				'defaults' => array(
					'booking_successful'   => array(
						'creds' => 10,
						'log'   => '%plural% for successful booking'
					)
				)
			), $hook_prefs, $type );
		}

		/**
		 * Run
		 */
		public function run() {
			// Hook into the custom action to award points on successful booking
			add_action('admin_appointment_booking', array( $this, 'award_points_for_successful_booking' ));
		}

		/**
		 * Award points for successful booking
		 */
		public function award_points_for_successful_booking( $data ) {


			// Check if the status is 'confirmed'
			if (isset($data['status']) && 'confirmed' === $data['status'] && isset($data['post_id'])) {
			
				// Get the user ID from post meta
				$user_id = get_post_meta($data['post_id'], 'wp_user', true); // Use the correct key

				// Check if a user ID exists
				if (!empty($user_id)) {
					// Get the preferences for booking success
					$prefs = $this->prefs['booking_successful'];

					// Award points using myCred
					$this->core->add_creds( 
					'booking_confirmation', 
					$user_id, 
					$prefs['creds'], 
					$prefs['log'], 
					$data['post_id'], 
					array( 'ref_type' => 'post' ), 
					$this->mycred_type 
					);
				} 
			}
		}



		/**
		 * Preferences
		 */
		public function preferences() {

			$prefs = $this->prefs;

			?>

		<div class="hook-instance">
			<h3><?php esc_html_e( 'Successful Booking', 'mycred-toolkit' ); ?></h3>
			<div class="row">
				<div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					<div class="form-group">
						<label for="<?php echo esc_attr( $this->field_id( array( 'booking_successful' => 'creds' ) ) ); ?>">
							<?php echo esc_html( $this->core->plural() ); ?>
						</label>
						<input type="text" class="form-control"
						name="<?php echo esc_attr( $this->field_name( array( 'booking_successful' => 'creds' ) ) ); ?>"
						id="<?php echo esc_attr( $this->field_id( array( 'booking_successful' => 'creds' ) ) ); ?>"
						value="<?php echo esc_attr( $this->core->number( $prefs['booking_successful']['creds'] ) ); ?>"
						size="8" />
					</div>
				</div>
				<div class="col-lg-8 col-md-8 col-sm-12 col-xs-12">
					<div class="form-group">
						<label for="<?php echo esc_attr( $this->field_id( array( 'booking_successful' => 'log' ) ) ); ?>">
							<?php esc_html_e( 'Log template', 'mycred-toolkit' ); ?>
						</label>
						<input type="text" class="form-control"
						name="<?php echo esc_attr( $this->field_name( array( 'booking_successful' => 'log' ) ) ); ?>"
						id="<?php echo esc_attr( $this->field_id( array( 'booking_successful' => 'log' ) ) ); ?>"
						value="<?php echo esc_attr( $prefs['booking_successful']['log'] ); ?>" />
						<span class="description">
							<?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) ) ); ?>
						</span>
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

			$data['booking_successful']['creds'] = ( !empty( $data['booking_successful']['creds'] ) ) 
			? floatval( $data['booking_successful']['creds'] ) 
			: $this->defaults['booking_successful']['creds'];

			$data['booking_successful']['log'] = ( !empty( $data['booking_successful']['log'] ) ) 
			? sanitize_text_field( $data['booking_successful']['log'] ) 
			: $this->defaults['booking_successful']['log'];

			return $data;
		}
	}
endif;
