<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'myCRED_frm_Quiz' ) ) :
	class myCRED_frm_Quiz extends myCRED_Hook {
		
		/**
		 * Construct
		 */
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'frm_quiz_submit',
				'defaults' => array(
					'creds'  => array(),
					'log'    => array(),
					'mycred_frm_action'  => array(),
					'mycred_frm_quiz'  => array(),
					'limit'  => array(),
					'mycred_frm_field_name'  => array(),
					'mycred_frm_field_val'  => array(),
					'limit_by'  => array(),
				)
			), $hook_prefs, $type );
		}

		/**
		 * Hook into WordPress
		 */
		public function run() {
			if ( isset($this->prefs['mycred_frm_action']) && !empty($this->prefs['mycred_frm_action']) && is_array($this->prefs['mycred_frm_action']) ) {
				add_action( 'forminator_quizzes_submit_before_set_fields', array( $this, 'on_frm_quiz_submission' ), 100, 3 );
			}
		}

		
		/**
		 * Runs when a forminator quiz is submitted
		 */
		public function on_frm_quiz_submission( $entry, $quiz_id, $field_data ) {

			$user_id = get_current_user_id();

			// Bail if user is not logged in
			if( $user_id === 0 ) return;

			// Check for exclusion
			if ( $this->core->exclude_user( $user_id ) ) return;

			$prefs = $this->prefs;
			
			if ( !empty($prefs) && is_array($prefs) ) {
				$refrence = 'frm_quiz_submit';

				for( $i =0; $i<count($prefs['mycred_frm_action']); $i++) {

					if ( 'frm_submit_quiz' == $prefs['mycred_frm_action'][$i] && !empty($prefs['creds'][$i]) && !$this->frm_over_hook_limit( $i, 'frm_submit_quiz', $user_id )) {
						
							$data = array (
								'mycred_frm_action' => $prefs['mycred_frm_action'][$i],
								'quiz_id' => $quiz_id,
							);
					
							$this->core->add_creds(
								'frm_submit_quiz',
								$user_id,
								$prefs['creds'][$i],
								$prefs['log'][$i],
								$quiz_id,
								$data
							);
				
					} else if( 'frm_submit_spec_quiz' == $prefs['mycred_frm_action'][$i] && !$this->frm_over_hook_limit( $i, 'frm_submit_spec_quiz', $user_id )) {
						if (isset($prefs['mycred_frm_quiz'][$i]) && !empty($prefs['mycred_frm_quiz'][$i]) && $prefs['mycred_frm_quiz'][$i] == $quiz_id) {
							$data = array (
								'mycred_frm_action' => $prefs['mycred_frm_action'][$i],
								'quiz_id' => $quiz_id,
							);
						
								$this->core->add_creds(
									'frm_submit_spec_quiz',
									$user_id,
									$prefs['creds'][$i],
									$prefs['log'][$i],
									$quiz_id,
									$data
								);
					
						}
					} else if( 'frm_submit_spec_field_quiz' == $prefs['mycred_frm_action'][$i] && !$this->frm_over_hook_limit( $i, 'frm_submit_spec_field_quiz', $user_id )) {
						
						if (isset($prefs['mycred_frm_field_name'][$i]) && !empty($prefs['mycred_frm_field_name'][$i]) &&
						isset($prefs['mycred_frm_field_val'][$i]) && !empty($prefs['mycred_frm_field_val'][$i])) {

							foreach ($field_data as $field) {
								if ($field['question'] == $prefs['mycred_frm_field_name'][$i] && $field['answer'] == $prefs['mycred_frm_field_val'][$i]) {
									$data = array (
										'mycred_frm_action' => $prefs['mycred_frm_action'][$i],
										'quiz_id' => $quiz_id,
										'field_name' => $prefs['mycred_frm_field_name'][$i],
										'field_value' => $prefs['mycred_frm_field_val'][$i],
									);
							
										$this->core->add_creds(
											'frm_submit_spec_field_quiz',
											$user_id,
											$prefs['creds'][$i],
											$prefs['log'][$i],
											$quiz_id,
											$data
										);
							
								}
							}

						}

					} else if( 'frm_submit_spec_field_spec_quiz' == $prefs['mycred_frm_action'][$i] && !$this->frm_over_hook_limit( $i, 'frm_submit_spec_field_spec_quiz', $user_id )) {
						if (isset($prefs['mycred_frm_quiz'][$i]) && $prefs['mycred_frm_quiz'][$i] == $quiz_id &&
						isset($prefs['mycred_frm_field_name'][$i]) && !empty($prefs['mycred_frm_field_name'][$i]) &&
						isset($prefs['mycred_frm_field_val'][$i]) && !empty($prefs['mycred_frm_field_val'][$i])) {

							foreach ($field_data as $field) {
								if ($field['question'] == $prefs['mycred_frm_field_name'][$i] && $field['answer'] == $prefs['mycred_frm_field_val'][$i]) {
									$data = array (
										'mycred_frm_action' => $prefs['mycred_frm_action'][$i],
										'quiz_id' => $quiz_id,
										'field_name' => $prefs['mycred_frm_field_name'][$i],
										'field_value' => $prefs['mycred_frm_field_val'][$i],
									);
									
									$this->core->add_creds(
										'frm_submit_spec_field_spec_quiz',
										$user_id,
										$prefs['creds'][$i],
										$prefs['log'][$i],
										$quiz_id,
										$data
									);
									
								}
							}

						}
					} else if( 'frm_pass_a_quiz' == $prefs['mycred_frm_action'][$i] && !$this->frm_over_hook_limit( $i, 'frm_pass_a_quiz', $user_id )) {

						$pass = true;
						foreach( $field_data as $data ) {
							// one wrong answer and user fails
							if( isset( $data['isCorrect'] ) && $data['isCorrect'] === false ) {
								$pass = false;
								break;
							}
						}

						if ($pass) {
							$data = array (
								'mycred_frm_action' => $prefs['mycred_frm_action'][$i],
								'quiz_id' => $quiz_id,
							);
					
							$this->core->add_creds(
								'frm_pass_a_quiz',
								$user_id,
								$prefs['creds'][$i],
								$prefs['log'][$i],
								$quiz_id,
								$data
							);
						}
						
					} else if( 'frm_pass_a_specific_quiz' == $prefs['mycred_frm_action'][$i] && !$this->frm_over_hook_limit( $i, 'frm_pass_a_specific_quiz', $user_id )) {
						if (isset($prefs['mycred_frm_quiz'][$i]) && !empty($prefs['mycred_frm_quiz'][$i]) && $prefs['mycred_frm_quiz'][$i] == $quiz_id) {
							$pass = true;
							foreach( $field_data as $data ) {
								// one wrong answer and user fails
								if( isset( $data['isCorrect'] ) && $data['isCorrect'] === false ) {
									$pass = false;
									break;
								}
							}

							if ($pass) {
								$data = array (
									'mycred_frm_action' => $prefs['mycred_frm_action'][$i],
									'quiz_id' => $quiz_id,
								);
						
								$this->core->add_creds(
									'frm_pass_a_specific_quiz',
									$user_id,
									$prefs['creds'][$i],
									$prefs['log'][$i],
									$quiz_id,
									$data
								);
							}
					
						}
						
					} else if( 'frm_fail_a_quiz' == $prefs['mycred_frm_action'][$i] && !$this->frm_over_hook_limit( $i, 'frm_fail_a_quiz', $user_id )) {

						$fail = false;
						foreach( $field_data as $data ) {
							// one wrong answer and user fails
							if( isset( $data['isCorrect'] ) && $data['isCorrect'] === false ) {
								$fail = true;
								break;
							}
						}

						if ($fail) {
							$data = array (
								'mycred_frm_action' => $prefs['mycred_frm_action'][$i],
								'quiz_id' => $quiz_id,
							);
					
							$this->core->add_creds(
								'frm_fail_a_quiz',
								$user_id,
								$prefs['creds'][$i],
								$prefs['log'][$i],
								$quiz_id,
								$data
							);
						}
						
					} else if( 'frm_fail_a_specific_quiz' == $prefs['mycred_frm_action'][$i] && !$this->frm_over_hook_limit( $i, 'frm_fail_a_specific_quiz', $user_id )) {
						if (isset($prefs['mycred_frm_quiz'][$i]) && !empty($prefs['mycred_frm_quiz'][$i]) && $prefs['mycred_frm_quiz'][$i] == $quiz_id) {
							
							$fail = false;
							foreach( $field_data as $data ) {
								// one wrong answer and user fails
								if( isset( $data['isCorrect'] ) && $data['isCorrect'] === false ) {
									$fail = true;
									break;
								}
							}
	
							if ($fail) {
								$data = array (
									'mycred_frm_action' => $prefs['mycred_frm_action'][$i],
									'quiz_id' => $quiz_id,
								);
						
								$this->core->add_creds(
									'frm_fail_a_specific_quiz',
									$user_id,
									$prefs['creds'][$i],
									$prefs['log'][$i],
									$quiz_id,
									$data
								);
							}
						}
					}
				}
			}
		}
		
		
		
		/**
		* Add Settings
		*/
		public function preferences() {

			// Our settings are available under $this->prefs
			
			$prefs = $this->prefs; 
			// echo "<pre>";
			// var_dump($prefs);
			// echo "</pre>";

			if ( isset($prefs['creds']) && count( $prefs['creds'] ) > 0 ) {
				$hooks = $this->mycred_frm_arrange_hook_data( $prefs );
			
				$this->mycred_frm_hook_settings( $hooks, $this );
			}
			else {
				$default_data = array(
					array(
						'creds'   => 1,
						'limit'   => 'x',
						'log'   => '%plural% for Forminator Quiz submit',
						'mycred_frm_quiz'   => '',
						'mycred_frm_field_name'   => '',
						'mycred_frm_field_val'   => '',
						'mycred_frm_action'   => 'submit_frm',
					)
				);
				$this->mycred_frm_hook_settings( $default_data, $this );
			}
		}

		/**
		 * Arrange hook data
		 */
		public function mycred_frm_arrange_hook_data( $data ){
			$hook_data = array();
			foreach ( $data['mycred_frm_action'] as $key => $value ) {
				$hook_data[$key]['creds'] = $data['creds'][$key];
				$hook_data[$key]['limit'] = $data['limit'][$key];
				$hook_data[$key]['log'] = $data['log'][$key];
				$hook_data[$key]['mycred_frm_quiz'] = $data['mycred_frm_quiz'][$key];
				$hook_data[$key]['mycred_frm_field_name'] = $data['mycred_frm_field_name'][$key];
				$hook_data[$key]['mycred_frm_field_val'] = $data['mycred_frm_field_val'][$key];
				$hook_data[$key]['mycred_frm_action'] = $value;
			}
			return $hook_data;
		}

        
		public function mycred_frm_hook_settings($data, $obj) {
			$actions = array(
				'frm_submit_quiz' => 'Successful submit a quiz',
				'frm_submit_spec_quiz' => 'Successful submit a specific quiz',
                'frm_pass_a_quiz' => 'Pass a quiz',
                'frm_pass_a_specific_quiz' => 'Pass a specific quiz',
                'frm_fail_a_quiz' => 'Fail a quiz',
                'frm_fail_a_specific_quiz' => 'Fail a specific quiz',
				'frm_submit_spec_field_quiz' => 'Submit a specific field value on any quiz',
				'frm_submit_spec_field_spec_quiz' => 'Submit a specific field value on a specific quiz'
			);

			$quizzes = Forminator_API::get_quizzes();

			$quizzes_data = array();
			foreach ($quizzes_data as $quiz) {
				$quizzes_data[$quiz->id] = $quiz->name;
			}
			//$form_data_json = json_encode($form_data);
			$count = 0;
			foreach ( $data as $prefs ) {
			?>
			<div class="hook-instance">
			
				<div class="row">
					<div class="col-lg-2 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($obj->field_id( 'creds' )); ?>"><?php echo esc_html($obj->core->plural()); ?></label>
							<input type="text" name="<?php echo $this->mycred_frm_field_name( $obj->mycred_type, 'creds' ); ?>" id="<?php echo esc_attr($obj->field_id( 'creds' )); ?>" value="<?php echo esc_attr($obj->core->number( $prefs['creds'] )); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-4 col-md-6 col-sm-6 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($obj->field_id( 'limit' )); ?>"><?php esc_html_e( 'Limit', 'mycred' ); ?></label>
							<?php echo wp_kses($obj->hook_limit_setting( $this->mycred_frm_field_name( $obj->mycred_type, 'limit' ), $obj->field_id( 'limit' ), $prefs['limit'] ),
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
						); ?>
						</div>
					</div>
					<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($obj->field_id( 'log' )); ?>"><?php esc_html_e( 'Log Template', 'mycred' ); ?></label>
							<input type="text" name="<?php echo $this->mycred_frm_field_name( $obj->mycred_type, 'log' ); ?>" id="<?php echo esc_attr($obj->field_id( 'log' )); ?>" placeholder="<?php esc_html_e( 'required', 'mycred' ); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
							<span class="description"><?php echo $obj->available_template_tags( array( 'general' ) ); ?></span>
						</div>
					</div>
				</div>

				<div class="row mycred-frm-quiz-fields">
				<div class="row">
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group frm-actions">
							<label ><?php esc_html_e( 'Actions', 'mycred' ); ?></label>
							<select class="form-control mycred-frm-quiz-actions" name="<?php echo $this->mycred_frm_field_name( $obj->mycred_type, 'mycred_frm_action' ); ?>">
								<?php
								
								foreach ($actions as $action_key => $action_label) {
									$selected = '';
									if ( $action_key == $prefs['mycred_frm_action']) {
										$selected = 'Selected';
									}
									?>
									<option value="<?php echo $action_key; ?>" <?php echo $selected; ?>><?php echo $action_label; ?></option>
									<?php
								}
								?>
							</select>
						</div>
					</div>
					<div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group frm-quizzes">
							<label ><?php esc_html_e( 'Quizes', 'mycred' ); ?></label>
							<select class="form-control mycred-frm-quizzes" name="<?php echo $this->mycred_frm_field_name( $obj->mycred_type, 'mycred_frm_quiz' ); ?>">
								<?php
								foreach ($quizzes as $quiz ) {
									$selected = '';
									if ( $quiz->id == $prefs['mycred_frm_quiz']) {
										$selected = 'Selected';
									}
									?>
									<option value="<?php echo $quiz->id; ?>" <?php echo $selected; ?>><?php echo $quiz->name; ?></option>
									<?php
								}
								?>
							</select>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="form-group frm-quiz-fields">
							<label ><?php esc_html_e( 'Field Name', 'mycred' ); ?></label>
							<input type="text" value="<?php echo esc_attr( $prefs['mycred_frm_field_name'] ); ?>" name="<?php echo $this->mycred_frm_field_name( $obj->mycred_type, 'mycred_frm_field_name' ); ?>" class="form-control frm-quiz-fields-name">
						</div>
					</div> 
					<div class="col-lg-3 col-md-3 col-sm-12 col-xs-12">
						<div class="form-group frm-quiz-fields">
							<label ><?php esc_html_e( 'Field Value', 'mycred' ); ?></label>
							<input type="text" value="<?php echo esc_attr( $prefs['mycred_frm_field_val'] ); ?>" name="<?php echo $this->mycred_frm_field_name( $obj->mycred_type, 'mycred_frm_field_val' ); ?>" class="form-control frm-quiz-fields-val">
						</div>
					</div>
				</div>
				</div>
				<?php 
				
				//if (count($data) > 1) {
					?>
					<div class="row">
						<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
							<div class="form-group mycred-frm-specific-hook-actions textright">
								<?php 
								if ($prefs === end($data)) {
									?>
									<button class="button button-small mycred-frm-quiz-add-hook" type="button">Add More</button>
									<?php
								} 
								?>
								<?php 
								//if ( $count >= 1) {
									?>
									<button class="button button-small mycred-frm-quiz-remove-hook" type="button">Remove</button>
									<?php
								//} 
								?>
							</div>
						</div>
					</div>
					<?php
			//	} 
			$count++;
				?>
				
			</div>
			<?php
				
			} // end foreach

			?>
			<script>
				jQuery(document).ready(function() {
					if (jQuery('.mycred-frm-quiz-actions').length) {
						jQuery('.mycred-frm-quiz-actions').each(function() {
							mycred_frm_quiz_hook_fields_display(jQuery(this).val(), jQuery(this));
						});
					}
				});
			</script>
			<?php
		}


		public function mycred_frm_field_name( $type, $attr ){

			$hook_prefs_key = 'mycred_pref_hooks';
	
			if ( $type != MYCRED_DEFAULT_TYPE_KEY ) {
				$hook_prefs_key = 'mycred_pref_hooks_'.$type;
			}
	
			return "{$hook_prefs_key}[hook_prefs][frm_quiz_submit][{$attr}][]";
		}

		/**
		 * Sanitize Preferences
		 */
		public function sanitise_preferences( $data ) {

			$new_data = array();
			foreach ( $data as $data_key => $data_value ) {
				foreach ( $data_value as $key => $value) {
					if ( $data_key == 'creds' ) {
						$new_data[$data_key][$key] = ( !empty( $value ) ) ? floatval( $value ) : 0;
					}
					else if ( $data_key == 'limit' ) {
						$limit = sanitize_text_field( $data[$data_key][$key]);
						if ( $limit == '' ) $limit = 0;
						$new_data[$data_key][$key] = $limit . '/' . $data['limit_by'][$key];
					}
					else if ( $data_key == 'log' ) {
						$new_data[$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '';
					}
					else if ( $data_key == 'mycred_frm_action' ) {
						$new_data[$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '';
					}
					else if ( $data_key == 'mycred_frm_quiz' ) {
						$new_data[$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '';
					}
					else if ( $data_key == 'mycred_frm_field_name' ) {
						$new_data[$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '';
					}
					else if ( $data_key == 'mycred_frm_field_val' ) {
						$new_data[$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '';
					}
				}
			} 

			return $new_data;

		}

		// customized function for mycred forminator
		public function frm_over_hook_limit( $instance = '', $reference = '', $user_id = NULL, $ref_id = NULL ) {

			// If logging is disabled, we cant use this feature
			if ( ! MYCRED_ENABLE_LOGGING ) return false;

			// Enforce limit if this function is used incorrectly
			if ( ! isset( $this->prefs['limit'][ $instance ] ) && $instance != '' )
				return true;

			global $wpdb, $mycred_log_table;

			// Prep
			$wheres = array();
			$now    = current_time( 'timestamp' );

			// If hook uses multiple instances
			// mycred forminator only uses multiple instances
			if ( isset( $this->prefs['limit'][ $instance ] ) )
				$prefs = $this->prefs['limit'][ $instance ];

			// no support for limits
			else {
				return false;
			}

			// If the user ID is not set use the current one
			if ( $user_id === NULL )
				$user_id = get_current_user_id();

			// If this an existance check or just a regular limit check?
			$exists_check = false;
			if ( $ref_id !== NULL && strlen( $ref_id ) > 0 )
				$exists_check = true;

			if ( count( explode( '/', $prefs ) ) != 2 )
				$prefs = '0/x';

			// Set to "no limit"
			if ( ! $exists_check && $prefs === '0/x' ) return false;

			// Prep settings
			list ( $amount, $period ) = explode( '/', $prefs );
			$amount   = (int) $amount;

			// We start constructing the query.
			$wheres[] = $wpdb->prepare( "user_id = %d", $user_id );
			$wheres[] = $wpdb->prepare( "ref = %s", $reference );
			$wheres[] = $wpdb->prepare( "ctype = %s", $this->mycred_type );

			if ( $exists_check )
				$wheres[] = $wpdb->prepare( "ref_id = %d", $ref_id );

			// If check is based on time
			if ( ! in_array( $period, array( 't', 'x' ) ) ) {

				// Per day
				if ( $period == 'd' )
					$from = mktime( 0, 0, 0, date( 'n', $now ), date( 'j', $now ), date( 'Y', $now ) );

				// Per week
				elseif ( $period == 'w' )
					$from = mktime( 0, 0, 0, date( "n", $now ), date( "j", $now ) - date( "N", $now ) + 1 );

				// Per Month
				elseif ( $period == 'm' )
					$from = mktime( 0, 0, 0, date( "n", $now ), 1, date( 'Y', $now ) );

				$wheres[] = $wpdb->prepare( "time BETWEEN %d AND %d", $from, $now );

			}

			$over_limit = false;

			if ( ! empty( $wheres ) ) {

				// Put all wheres together into one string
				$wheres   = implode( " AND ", $wheres );

				$query = "SELECT COUNT(*) FROM {$mycred_log_table} WHERE {$wheres};";

				//Lets play for others
				$query = apply_filters( 'mycred_frm_hook_limit_query', $query, $instance, $reference, $user_id, $ref_id, $wheres, $this );

				// Count
				$count = $wpdb->get_var( $query );
				if ( $count === NULL ) $count = 0;

				// Existence check has first priority
				if ( $count > 0 && $exists_check )
					$over_limit = true;

				// Limit check is second priority
				elseif ( $period != 'x' && $count >= $amount )
					$over_limit = true;

			}

			return apply_filters( 'mycred_frm_over_hook_limit', $over_limit, $instance, $reference, $user_id, $ref_id, $this );

		}
	}
endif;
