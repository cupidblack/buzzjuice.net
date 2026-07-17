<?php
if (! defined('ABSPATH')) {
exit;
}

/**
 * The class_exists() check is recommended, to prevent problems during upgrade
 * or when the Groups component is disabled
 */
if (! class_exists('myCRED_LifterLMS_Certificate')) :
	class myCRED_LifterLMS_Certificate extends myCRED_Hook {
		
		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'mycred_lifterlms_certificate',
				'defaults' => array(
					'creds'  => 1,
					'log'    => '%plural% for earning a new certificate',
					'limit'  => '0/x'
				)
			), $hook_prefs, $type);
		}

		/**
		 * Hook into WordPress
		 */
		public function run() {
			
			if ( $this->prefs['creds'] !== 0 ) {
				add_action( 'llms_user_earned_certificate', array( $this, 'user_earned_certificate' ), 100, 3 );
			}
		}

		/**
		 * Runs when a student earns new certificate
		 *
		 * @since 1.0
		 *
		 * @param $student_id
		 * @param $certificate_id
		 * @param $related_post_id
		 */
		public function user_earned_certificate( $student_id, $certificate_id, $related_post_id ) {
			// Check for exclusion
			if ( $this->core->exclude_user( $student_id ) ) {
return;
			}

			$refrence = 'llms_user_earned_certificate';

			$data = array(
				'certificate'       => $certificate_id,
				'related_post_id'   => $related_post_id
			);
			
			// Enforce limit and make sure users only get points once per unique certificate
			if ( ! $this->over_hook_limit( '', $refrence, $student_id ) && ! $this->core->has_entry( $refrence, $student_id, $certificate_id, $data, $this->mycred_type ) ) {
				$this->core->add_creds(
					$refrence,
					$student_id,
					$this->prefs['creds'],
					$this->prefs['log'],
					$certificate_id,
					$data
				);
			}
		}

		/**
		* Add Settings
		*/
		public function preferences() {
			// Our settings are available under $this->prefs
			$prefs = $this->prefs; ?>

			<div class="hook-instance">
				<div class="row">
					<div class="col-lg-2 col-md-2 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( 'creds' )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( 'creds' )); ?>" id="<?php echo esc_attr($this->field_id( 'creds' )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['creds'] )); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( 'limit' )); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
							<?php
							echo wp_kses($this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), esc_attr( $prefs['limit'] ) ),
							  array(
								  'div' => array(
									  'class' => array()
								  ),
								  'input' => array(
									  'type' => array(),
									  'size' => array(),
									  'class' => array(),
									  'name' => array(),
									  'id' => array(),
									  'value' => array()
								  ),
								  'select' => array(
									  'name' => array(),
									  'id' => array(),
									  'class' => array()
								  ),
								  'option' => array(
									  'value' => array(),
									  'selected' => array()
								  )
							  )
						);
							?>
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( 'log' )); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( 'log' )); ?>" id="<?php echo esc_attr($this->field_id( 'log' )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
							<span class="description"><?php echo wp_kses_post( $this->available_template_tags( array( 'general' ) )); ?></span>
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

			if (isset($data['limit']) && isset($data['limit_by'])) {
				$limit = sanitize_text_field($data['limit']);
				if ($limit == '') {
$limit = 0;
				}
				$data['limit'] = $limit . '/' . $data['limit_by'];
				unset($data['limit_by']);
			}

			return $data;
		}
	}
endif;

?>