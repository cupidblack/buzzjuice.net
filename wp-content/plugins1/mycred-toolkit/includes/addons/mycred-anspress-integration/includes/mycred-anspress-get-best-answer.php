<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * mycred-anspress best answer hook
 *
 * myCRED_Addons_Module class
 *
 * @since       1.0.0
 * @package     mycred-anspress
 * @subpackage  mycred-anspress/includes
 */


if ( ! class_exists( 'myCRED_Anspress_Get_Best_Answer_Hook' ) ) :
	class myCRED_Anspress_Get_Best_Answer_Hook extends myCRED_Hook {

		/**
		 * Construct
		 *
		 * @param $hook_prefs
		 * @param $type
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'anspress_ap_get_best_answer',
				'defaults' => array(
					'creds'    => 10,
					'log'      => '%plural% for answer gets selected as best.',
					'limit'    => 1,
					'limit_by' => 'day'
				)
			), $hook_prefs, $type );
		}

		/**
		 * Class run function       all action hooks defines here.
		 *
		 * @access public
		 *
		 * @since 1.8
		 * @version 1.0
		 */
		public function run() {

			add_action( 'ap_select_answer', array( $this, 'anspress_best_answer' ), 10, 2 );
		}

		/**
		 * Calls When a user gets an answer select as best
		 *
		 * points will be awarded to the author of the answer
		 *
		 * @param $post_id
		 * @param $question_id
		 *
		 * @since 1.8
		 * @version 1.0
		 */
		public function anspress_best_answer( $post, $question_id ) {
			$answer_id   = $post->ID;
			$user_id     = $post->post_author;
			$selector_id = get_current_user_id();
			$answer      = $post;
			$question    = get_post( $question_id );

			if ( $selector_id === 0 || $user_id === 0 ) {
				return;
			}

			$hook_prefs_key = 'mycred_pref_hooks';

			if ( $this->mycred_type != MYCRED_DEFAULT_TYPE_KEY ) {
				$hook_prefs_key = 'mycred_pref_hooks_' . $this->mycred_type;
			}

			$hooks = get_option( $hook_prefs_key, false );


			// Make sure user is not excluded
			if ( isset( $user_id ) ) {
				if ( $this->core->exclude_user( $user_id ) ) {
					return;
				}
			}

			$ref_type = array( 'ref_type' => 'post' );
			if ( $this->has_entry( 'anspress_ap_get_best_answer', 'anspress_ap_get_best_answer', $user_id, $this->prefs['creds'], $this->prefs['log'] ) ) {
				return;
			}
			if ( $this->over_hook_limit( 'limit', 'anspress_ap_get_best_answer', $user_id, '' ) ) {
return;
			}

			$resp = $this->core->add_creds(
				'anspress_ap_get_best_answer',
				$user_id,
				$this->prefs['creds'],
				$this->prefs['log']
			);
		}

		/**
		 * Preference for best answer hook
		 * @since 1.8
		 * @version 1.0
		 */
		public function preferences() {

			$prefs = $this->prefs;
			?>
			<div class="hook-instance">
				<span></span>
				<div class="row">
					<div class="col-lg-2 col-md-2 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo $this->field_id( 'creds' ); ?>"><?php echo $this->core->plural(); ?></label>
							<input type="text" name="<?php echo $this->field_name( 'creds' ); ?>"
								   id="<?php echo $this->field_id( 'creds' ); ?>"
								   value="<?php echo $this->core->number( $prefs['creds'] ); ?>" class="form-control"/>
						</div>
					</div>
					<div class="col-lg-3 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo $this->field_id( 'limit' ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
							<?php echo $this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), esc_attr( $prefs['limit'] ) ); ?>
						</div>
					</div>
					<div class="col-lg-5 col-md-5 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo $this->field_id( 'log' ); ?>"><?php esc_html_e( 'Log Template', 'mycred-toolkit' ); ?></label>
							<input type="text" name="<?php echo $this->field_name( 'log' ); ?>"
								   id="<?php echo $this->field_id( 'log' ); ?>"
								   placeholder="<?php esc_html_e( 'required', 'mycred-toolkit' ); ?>"
								   value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control"/>
							<span class="description"><?php echo $this->available_template_tags( array( 'general' ) ); ?></span>
						</div>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Sanitize Preferences
		 *
		 * @param  $data
		 *
		 * @since 1.0.0
		 */
		public function sanitise_preferences( $data ) {
			$new_data = $data;
			// Apply defaults if any field is left empty
			$new_data['creds'] = ( ! empty( $data['creds'] ) ) ? sanitize_text_field( $data['creds'] ) : $this->defaults['creds'];
			$new_data['log']   = ( ! empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];

			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['limit'] );
				if ( $limit == '' ) {
					$limit = 0;
				}
				$new_data['limit'] = $limit . '/' . $data['limit_by'];
				unset( $data['limit_by'] );
			}

			return $new_data;
		}
	}
endif;