<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
* myCRED_Addons_Module class
**/
if ( ! class_exists( 'myCRED_GWP_Multiple_Hook' ) ) :
	class myCRED_GWP_Multiple_Hook extends myCRED_Hook {

		/**
		* Construct
		**/
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {
			parent::__construct( array(
				'id'       => 'mycred_give_wp_multiple',
				'defaults' => array(
					'creds'    => array(),
					'limit'    => array(),
					'log'      => array(),
					'type'     => $type,
					'gwp_form_id'  => array(),
					'gwp_minimum_amount'  => array(),
				)
			), $hook_prefs, $type );
		}

		/**
		* Run Function
		**/
		public function run() {
			add_action( 'wp_ajax_myCred_gwp_save_entry', array( $this, 'myCred_gwp_save_entry' ));
			add_action( 'wp_ajax_nopriv_myCred_gwp_save_entry', array( $this, 'myCred_gwp_save_entry' ));
		}
		
		/** 
		*   myCred save entry
		**/
		public function myCred_gwp_save_entry() {
					
			$form_id = isset($_POST['form_id']) ?  absint( $_POST['form_id'] ) : 0;
			$give_amount = isset( $_POST['give_amount']) ? sanitize_text_field($_POST['give_amount']) : 0;
			$give_form_title = isset( $_POST['give_form_title']) ? sanitize_text_field( $_POST['give_form_title'] ) : '';
			
			$gwp_form_id = $this->prefs['gwp_form_id'];
			// Make sure user is not excluded
			if (is_user_logged_in()) {
				$user = wp_get_current_user();
				$user_id = $user->ID;
				if ( ! $this->core->exclude_user( $user_id ) ) {
					if (!empty($gwp_form_id)) :
						foreach ($gwp_form_id as $key => $val) :
							$limit  =   $this->prefs['limit'][ $key ];
							$type   =   $this->prefs['type'];
							$creds  =   $this->prefs['creds'][ $key ];
							$log    =   $this->prefs['log'][ $key ];
							$gwp_minimum_amount =   $this->prefs['gwp_minimum_amount'][ $key ];
							//Remove comma form amount
							$new_give_amount = str_replace( ',', '', $give_amount);
							$give_amount = absint( $new_give_amount );
							$ref_type  = array( 'ref_type' => 'post' );
							if ($val == $form_id) {
								$response = $this->get_user_limit($limit, $user_id, $type);
								if ($response == true) {
									if ($gwp_minimum_amount > 0) {
										if ($give_amount >= $gwp_minimum_amount) {
											mycred_add('mycred_give_wp_multiple', $user_id, $creds, $log . ' ' . $give_form_title, $form_id, $ref_type, $type);
										}
									} else {
										mycred_add('mycred_give_wp_multiple', $user_id, $creds, $log . ' ' . $give_form_title, $form_id, $ref_type, $type);
									}
								}
							} else if ($val == 999999) {
								$response = $this->get_user_limit($limit, $user_id, $type);
								if ($response == true) {  
									if ($gwp_minimum_amount >0) {
										if ($give_amount >= $gwp_minimum_amount) {
											mycred_add('mycred_give_wp_multiple', $user_id, $creds, $log . ' ' . $give_form_title, $form_id, $ref_type, $type);
										}
									} else {
										mycred_add('mycred_give_wp_multiple', $user_id, $creds, $log . ' ' . $give_form_title, $form_id, $ref_type, $type);
									}
								}
							}
						endforeach;
					endif; 
				}
			}
		} 
		
		/**
		* $limit = 2/d , 3/w, 5/m, 10/t
		* $user_id = current user id
		* $ctype = point type
		**/
		public function get_user_limit( $limit, $user_id, $ctype ) {
			$limit_period = explode( '/', $limit);
			$time = $limit_period[0]; //
			$period = $limit_period[1]; // d,m,w,t
			$date_to_check = ''; // no limit
			if ( $period == 'm' ) {
				$date_to_check = 'thismonth';
			} else if ( $period == 'w' ) {
				$date_to_check = 'thisweek';
			} else if ( $period == 'd' ) {
				$date_to_check = 'today';
			} else if ( $period == 't' ) {
				$date_to_check = 'total';
			} else { // when no limit set
				return true;
			}
			
			$args = array(
				'ref' => array(
					'ids' => 'mycred_give_wp_multiple',
					'compare' => '='
				),
				'user_id'   => $user_id,
				'ctype'     => $ctype,
				'date'     => $date_to_check,
			);
			$log  = new myCRED_Query_Log( $args );
			$used_limit = $log->num_rows;
			
			if ( $used_limit >= $time ) {
				return false;
			}
			
			return true;
		}
		
		/**
		* Preference for give wp hook
		**/
		public function preferences() {
			$prefs = $this->prefs;
			if ( isset($prefs['creds']) && count( $prefs['creds'] ) > 0 ) {
				$hooks = myCred_GWP_Arrange_Data( $prefs );
				myCred_GWP_Hook_Setting( $hooks, $this );
			} else {
				$default_data = array(
					array(
						'creds' => 10,
						'limit' => 'x',
						'log' => '%plural% for specific form completing',
						'gwp_form_id' => '0',
						'gwp_minimum_amount' => '0',
					)
				);
				myCred_GWP_Hook_Setting( $default_data, $this );
			}
		}

	   /**
	   * Sanitize Preferences
	   */
		public function sanitise_preferences( $data ) {
			foreach ( $data as $data_key => $data_value ) {
				foreach ( $data_value as $key => $value) {
					if ( $data_key == 'creds' ) {
						$new_data[ $data_key ][ $key ] = ( !empty( $value ) ) ? floatval( $value ) : 0;
					} else if ( $data_key == 'limit' ) {
						$limit = sanitize_text_field( $data[ $data_key ][ $key ]);
						if ( $limit == '' ) {
$limit = 0;
						}
						$new_data[ $data_key ][ $key ] = $limit . '/' . $data['limit_by'][ $key ];
					} else if ( $data_key == 'log' ) {
						$new_data[ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for specific form zoom meeting completing.';
					} else if ( $data_key == 'gwp_form_id' ) {
						$new_data[ $data_key ][ $key ] = ( !empty( $value ) ) ? intval( $value ) : 0;
					} else if ( $data_key == 'gwp_minimum_amount' ) {
						$new_data[ $data_key ][ $key ] = ( !empty( $value ) ) ? intval( $value ) : 0;
					}
					
				}
			} 
			return $new_data;
		}
	}
endif;
