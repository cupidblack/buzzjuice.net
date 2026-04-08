<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Total Survey Hook
 *
 * @since 1.0.0
 * @version 1.0.0
 */

if (!class_exists('MyCred_Hook_Total_Survey') && class_exists('myCRED_Hook')) {

	class MyCred_Hook_Total_Survey extends myCRED_Hook {
		/**
		 * Construct
		 */
		public function __construct( $hook_prefs, $type = 'mycred_default' ) {
			parent::__construct(array(
				'id' => 'totalsurvey',
				'defaults' => array(
					'creds' => 0,
					'log' => __('%plural% for Completing a General Survey.', 'mycred-toolkit'),
					'limit' => '0/x',
					'check_specific_hook' => 0,
					'specific_survey_completed' => array(
						'creds' => array(),
						'log' => array(),
						'limit' => array(), 
						'select_survey' => array(), 
					),
				)
			), $hook_prefs, $type);
		}

		 /**
		 * Run
		 */
		public function run() {

			add_action( 'totalsurvey/entry/received', array( $this, 'handle_totalsurvey_entry' ), 10, 1 );
		}

		public function handle_totalsurvey_entry( $event ) {
			
			$user_id = $event->entry->user_id;
			$survey_id = $event->survey->id;
			$survey_uid = $event->survey->uid;
			$prefs = $this->prefs;
			$amount = ( !empty($prefs['creds']) ) ? $prefs['creds'] : '';
			$entry = ( !empty($prefs['log']) ) ? $prefs['log'] : '';
			foreach ( $prefs['specific_survey_completed']['select_survey'] as $key => $value ) {

				if ( $survey_uid == $value ) {
					$specific_amount = $prefs['specific_survey_completed']['creds'][ $key ];
					$specific_entry = $prefs['specific_survey_completed']['log'][ $key ];
				}
			}

			// if specific survey disabled
			if ( '0' == $prefs['check_specific_hook']  ) {

				// Check General Limit
				if ( $this->over_hook_limit( $survey_id, 'total_survey', $user_id, $survey_id, $survey_uid ) ) {
					return;
				}
				// Execute
				$this->core->add_creds(
					'total_survey',
					$user_id,
					$amount,
					$entry,
					$survey_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);
			} elseif ( $prefs['check_specific_hook'] == '1' && !empty( $prefs['specific_survey_completed']['select_survey'] ) && isset( $specific_amount) && !empty( $prefs['specific_survey_completed']['log'] ) && isset( $specific_entry) && !empty($prefs['specific_survey_completed']['select_survey']) ) {


				// Check Specific Limit
				if ( $this->over_hook_limit( $survey_id, 'total_survey', $user_id, $survey_id, $survey_uid ) ) {
					return;
				}

				// Execute
				$this->core->add_creds(
					'total_survey',
					$user_id,
					$specific_amount,
					$specific_entry,
					$survey_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);

			} else {

				// Execute
				$this->core->add_creds(
					'total_survey',
					$user_id,
					$amount,
					$entry,
					$survey_id,
					array( 'ref_type' => 'post' ),
					$this->mycred_type
				);

			}
		}

		/**
		 * Check Limit
		 *
		 * @since 1.0.0
		 * @version 1.0.0
		 */
		public function over_hook_limit( $instance = '', $reference = '', $user_id = null, $ref_id = null, $survey_uid = null ) {

			// If logging is disabled, we cant use this feature
			if ( ! MYCRED_ENABLE_LOGGING ) {
				return false;
			}

			global $wpdb, $mycred_log_table;

			// Prep
			$wheres = array();
			$now    = current_time( 'timestamp' );
			$prefs = '';

			// If Hook Has General Limit
			if ( isset( $this->prefs['check_specific_hook'] ) && 1 != $this->prefs['check_specific_hook'] ) {
				$prefs = isset( $this->prefs['limit'] ) ? $this->prefs['limit'] : '';
			} elseif ( isset( $this->prefs['check_specific_hook'] ) && 0 != $this->prefs['check_specific_hook'] ) {
				foreach ( $this->prefs['specific_survey_completed']['select_survey'] as $key => $value ) {
					if ( $survey_uid == $value ) {
						$prefs = $this->prefs['specific_survey_completed']['limit'][ $key ];
					}
				}
			}
		
			// If the user ID is not set use the current one
			if ( null === $user_id ) {
				$user_id = get_current_user_id();
			}

			if ( is_string( $prefs ) ) {
				$parts = explode( '/', $prefs );
				if ( count( $parts ) != 2 ) {
					$prefs = '0/x';
				} else {
					list( $amount, $period ) = $parts; // Assign values to $amount and $period
				}
			}

			// Set to "no limit"
			if ( '0/x' === $prefs ) {
				return false;
			}

			// Prep settings
			if ( is_string( $prefs ) ) {
				list( $amount, $period ) = explode( '/', $prefs );
			}

			if ( is_string( $prefs ) ) {
				list( $amount, $period ) = explode( '/', $prefs );
			
				// Convert $amount to an integer if it's a string
				if ( isset( $amount ) ) {
					$amount = (int) $amount;
				} 
			}

			// We start constructing the query.
			$wheres[] = $wpdb->prepare( 'user_id = %d', $user_id );
			$wheres[] = $wpdb->prepare( 'ref = %s', $reference );
			$wheres[] = $wpdb->prepare( 'ctype = %s', $this->mycred_type );
			$wheres[] = $wpdb->prepare( 'ref_id = %d', $ref_id );

			// If check is based on time
			if ( isset( $period ) && ! in_array( $period, array( 't', 'x' ) ) ) {

				// Per day
				if ( 'd' == $period ) {
					$from = mktime(0, 0, 0, gmdate('n', $now), gmdate('j', $now), gmdate('Y', $now));
				} elseif ( 'w' == $period ) {
					$from = mktime(0, 0, 0, gmdate('n', $now), gmdate('j', $now) - gmdate('N', $now) + 1);
				} elseif ( 'm' == $period ) {
					$from = mktime(0, 0, 0, gmdate('n', $now), 1, gmdate('Y', $now));
				}

				$wheres[] = $wpdb->prepare( 'time BETWEEN %d AND %d', $from, $now );

			}

			$over_limit = false;
			
			if ( ! empty( $wheres ) ) {

				// Put all wheres together into one string
				$wheres   = implode( ' AND ', $wheres );

				// $query = "SELECT COUNT(*) FROM {$mycred_log_table} WHERE {$wheres};";

				/**
				* Filter mycred_custom_hook_limit_query
				* 
				* @since 1.0.0
				**/
				// $query = apply_filters( 'mycred_custom_hook_limit_query', $query, $instance, $reference, $user_id, $ref_id, $wheres, $this );

				// Count
				$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE {$wheres};", $mycred_log_table ) );

				if ( null === $count ) {
					$count = 0;
				} else {
					$over_limit = true;
				}

			}

			/**
			* Action mycred_custom_over_hook_limit
			* 
			* @since 1.0.0
			**/
			return apply_filters( 'mycred_custom_over_hook_limit', $over_limit, $instance, $reference, $user_id, $ref_id, $this );
		}

		/**
		 * Preferences for TotalSurvey
		 */
		public function specific_field_name( $field = '' ) {

		   $hook_prefs_key = 'mycred_pref_hooks';

			if ( is_array( $field ) ) {
				$array = array();
				foreach ( $field as $parent => $child ) {
					if ( ! is_numeric( $parent ) ) {
					   $array[] = $parent;
					}

					if ( ! empty( $child ) && !is_array( $child ) ) {
					   $array[] = $child;
					}
				}
				$field = '[' . implode( '][', $array ) . ']';
			} else {
				$field = '[' . $field . ']';
			}

		   $option_id = 'mycred_pref_hooks';
			if ( ! $this->is_main_type ) {
			$option_id = $option_id . '_' . $this->mycred_type;
			}

		   return $option_id . '[hook_prefs][' . $this->id . ']' . $field . '[]';
		}

		public function mycred_totalsurvey_arrange_data( $specific_hook_data ) {
				
				
			$hook_data = array();
			foreach ( $specific_hook_data['creds'] as $key => $value ) {

				$hook_data[ $key ]['creds']      = $value;
				$hook_data[ $key ]['log']        = isset( $specific_hook_data['log'][ $key ] ) ? $specific_hook_data['log'][ $key ] : '' ;
				$hook_data[ $key ]['select_survey'] = isset( $specific_hook_data['select_survey'][ $key ] ) ? $specific_hook_data['select_survey'][ $key ] : '';
				$hook_data[ $key ]['limit'] =  $specific_hook_data['limit'][ $key ] ;
			}
			return $hook_data;
		}

		public function preferences() {
			global $wpdb;
			$prefix = $wpdb->prefix;
			$table = $prefix . 'totalsurvey_surveys';
			$survey_data = $wpdb->get_results( $wpdb->prepare( 'SELECT id, uid, name, enabled FROM %i', $table ) );
			$prefs = $this->prefs;
			?>
			<!-- General Total Survey Complete Starts -->
			<div class="hook-instance">
				<h3><?php esc_html_e( 'General', 'mycred-toolkit' ); ?></h3>
				<div class="row">
					<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id( 'creds' )); ?>"><?php echo esc_html($this->core->plural()); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( 'creds' )); ?>" id="<?php echo esc_attr($this->field_id( 'creds' )); ?>" value="<?php echo esc_attr($this->core->number( $prefs['creds'] )); ?>" class="form-control" />
						</div>
					</div>
					<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr( $this->field_id( array( 'giving' => 'limit' ) ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
							<?php 
							echo wp_kses(
									$this->hook_limit_setting( $this->field_name( 'limit' ), $this->field_id( 'limit' ), esc_attr( $prefs['limit'] ) ),
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
					<div class="col-lg-6 col-md-12 col-sm-12 col-xs-12">
						<div class="form-group">
							<label for="<?php echo esc_attr($this->field_id('log' )); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
							<input type="text" name="<?php echo esc_attr($this->field_name( 'log' )); ?>" id="<?php echo esc_attr($this->field_id( 'log' )); ?>" value="<?php echo esc_attr( $prefs['log'] ); ?>" class="form-control" />
						</div>
					</div>
				</div>
			</div>

			<!-- General Total Survey Complete Ends -->

			<!-- Specific Total Survey Complete Starts -->
			<?php
			$survey_complete_data = array(
				array(
					'creds' => 0,
					'log' => __('%plural% for Completing a Specific Survey.', 'mycred-toolkit'),
					'limit' => '0/x',
					'select_survey' => array(),
				),
			);
			$default_log = __('%plural% for Completing a Specific Survey.', 'mycred-toolkit');
			if ( count(  $prefs['specific_survey_completed']['creds'] ) > 0 ) {

				$survey_complete_data = $this->mycred_totalsurvey_arrange_data( $prefs['specific_survey_completed'] );
			}
			?>
				<div id="mycred_totalsurvey_default_log" hidden><?php echo esc_attr( $default_log ); ?></div>
				<div class="hook-instance" id="specific-hook">
					<div class="row">
						<div class="col-lg-12">
							<div class="hook-title">
								<h3><?php esc_html_e( 'Specific', 'mycred-toolkit' ); ?></h3>
							</div>
						</div>
					</div>
					<div class="checkbox" style="margin-bottom:14px;">
						<input type="checkbox" id="<?php echo esc_attr($this->field_id('check_specific_hook')); ?>" name="<?php echo esc_attr($this->field_name('check_specific_hook')); ?>" value="1" 
															  <?php 
																if ( '1' == $prefs['check_specific_hook']) {
										echo "checked = 'checked'";} 
																?>
						>
							<label class="specific-hook-checkbox" for="<?php echo esc_attr($this->field_id('check_specific_hook')); ?>"><?php esc_html_e( 'Enable Specific Hook', 'mycred-toolkit' ); ?></label>
					</div> 
					<?php
					foreach ( $survey_complete_data as $hook => $label ) {
						?>
					<div class="survey_custom_hook_class">
						<div class="row">
							<div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
								<div class="form-group">
									<label><?php esc_html_e( 'Select Survey', 'mycred-toolkit' ); ?></label>
									<select class="form-control  mycred_total_survey_select" id="mycred_total_survey_select" name="<?php echo esc_attr( $this->specific_field_name( array( 'specific_survey_completed' => 'select_survey' ) ) ); ?>" value="" >
										
										<option value="0">Select Survey</option>
										<?php
										foreach ( $survey_data as $key => $value ) {
											$selected = isset( $label['select_survey'] ) && $label['select_survey'] == $value->uid ? 'selected' : '';
												
											echo '<option class="select-value" value="' . esc_attr($value->uid ) . '" ' . esc_html( $selected ) . ' >' . esc_html($value->name) . '</option>\n';
										}
										?>
									</select>
								</div>
							</div>
							<div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
								<div class="form-group">
									<label for="<?php echo esc_attr($this->field_id(array( 'specific_survey_completed' => 'creds' ))); ?>"><?php echo esc_html($this->core->plural()); ?></label>
									<input type="text" name="<?php echo esc_attr($this->specific_field_name(array( 'specific_survey_completed' => 'creds' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'specific_survey_completed' => 'creds' ))); ?>" value="<?php echo esc_attr($this->core->number( $label['creds'])); ?>" class="form-control mycred-totalsurvey-specific-creds" />
								</div>
							</div>
							<div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
								<div class="form-group mycred_totalsurvey_limit_select" style="margin:0; padding:0;">
									<label for="<?php echo wp_kses_post( $this->field_id( array( 'specific_survey_completed' => 'limit' ) ) ); ?>"><?php esc_html_e( 'Limit', 'mycred-toolkit' ); ?></label>
									<?php 
									echo wp_kses( 
										$this->hook_limit_setting( $this->specific_field_name( array( 'specific_survey_completed' => 'limit' ) ), '', esc_attr( $label['limit'] ) ),
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
							<div class="col-lg-3 col-md-12 col-sm-12 col-xs-12">
								<div class="form-group" style="margin:0; padding:0">
									<label for="<?php echo esc_attr($this->field_id(array( 'specific_survey_completed' => 'log' ))); ?>"><?php esc_html_e('Log Template', 'mycred-toolkit'); ?></label>
									<input type="text" name="<?php echo esc_attr($this->specific_field_name(array( 'specific_survey_completed' => 'log' ))); ?>" id="<?php echo esc_attr($this->field_id(array( 'specific_survey_completed' => 'log' ))); ?>" value="<?php echo esc_attr($label['log']) ; ?>" class="form-control mycred-totalsurvey-specific-logs" />
								</div>
							</div>
						</div>
						<div class="row">
							<div class="row add_more_row">
								<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12  field_wrapper">
									<div class="form-group specific-hook-actions textright" >
										<button class="button button-small mycred-add-specific-survey-hook add_button" id="clone_btn" type="button"><?php esc_html_e( 'Add More', 'mycred-toolkit' ); ?></button>
										<button class="button button-small mycred-remove-survey-specific-hook" type="button"><?php esc_html_e( 'Remove', 'mycred-toolkit' ); ?></button>
									</div>
								</div>
							</div>
						</div> 
						<div class="row line-break-row"></div>
					</div>
					<?php
					} 
					?>
				</div>
			<?php
		}
		public function sanitise_preferences( $data ) {
			
			$new_data = array();

			$new_data['creds'] = ( !empty( $data['creds'] ) ) ? floatval( $data['creds'] ) : $this->defaults['creds'];
			$new_data['check_specific_hook'] = ( !empty( $data['check_specific_hook'] ) ) ? sanitize_text_field( $data['check_specific_hook'] ) : $this->defaults['check_specific_hook'];
			$new_data['log'] = ( !empty( $data['log'] ) ) ? sanitize_text_field( $data['log'] ) : $this->defaults['log'];

			if ( isset( $data['limit'] ) && isset( $data['limit_by'] ) ) {
				$limit = sanitize_text_field( $data['limit'] );
				if ( '' == $limit ) {
					$limit = 0;
				}
				$new_data['limit'] = $limit . '/' . $data['limit_by'];
				unset( $data['limit_by'] );
			}

			foreach ( $data['specific_survey_completed'] as $data_key => $data_value ) {

				foreach ( $data_value as $key => $value) {
					
					if ( 'creds' == $data_key ) {
						$new_data['specific_survey_completed'][ $data_key ][ $key ] = ( !empty( $value ) ) ? floatval( $value ) : 0;
					} else if ( 'log' == $data_key ) {
						$new_data['specific_survey_completed'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : __('%plural% for Completing a Specific Survey.', 'mycred-toolkit');
					} else if ( 'select_survey' == $data_key ) {
						$new_data['specific_survey_completed'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '';
					} else if ( 'limit' == $data_key ) {
						$new_data['specific_survey_completed'][ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value . '/' . $data['specific_survey_completed']['limit_by'][ $key ] ) : '0';
					}
				}
			}

			return $new_data;
		}
	}

}
