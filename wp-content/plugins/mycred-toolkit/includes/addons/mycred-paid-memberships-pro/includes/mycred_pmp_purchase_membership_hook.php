<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
* myCred Purchase Membership Hook
**/
if ( ! class_exists( 'myCred_purchase_membership_hook' ) ) :
	class myCred_purchase_membership_hook extends myCRED_Hook {

		/**
		* Construct
		**/
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {
			parent::__construct( array(
				'id'       => 'mycred_pmp_purchase_membership',
				'defaults' => array(
					'creds'    => array(),
					'limit'    => array(),
					'log'      => array(),
					'pmp_form_id'  => array(),
				)
			), $hook_prefs, $type );
		}

		/**
		* Run Function
		**/
		public function run() {
			
			add_action('pmpro_after_checkout', array($this, 'award_points_after_successful_checkout'), 100, 2);

			// This hook fires when membership level changes
			add_action('pmpro_after_change_membership_level', array($this, 'award_points_on_level_change'), 100, 3);

			add_action('pmpro_updated_order', array($this, 'handle_order_status_change'), 100, 1);

			add_action( 'pmpro_order_status_success', array( $this, 'award_points_on_success_purchase' ), 2000, 2 );

		}

		public function award_points_on_success_purchase( $order, $old_status ) {

				if (isset($order->status) && $order->status !== 'success') {
					return;
				}

				if ( $order->is_renewal()) {
					return;
				}

				$user = $order->getUser();
				$membership = $order->getMembershipLevel();
				
				if (!$user || !$membership) {
					return;
				}
				
				$user_id = $user->ID;
				$membership_id = $membership->id;

				$pmp_form_id = $this->prefs['pmp_form_id'];
		        $ref_type = array('ref_type' => 'post');
		        $order_id = $order->id;

				if ( $this->core->has_entry( 'mycred_pmp_purchase_membership', $order->id, $user_id ) ) {
					return;
				}
				
				if (!empty($pmp_form_id)) {
					foreach ($pmp_form_id as $key => $val) {
						$limit = $this->prefs['limit'][$key];
						$type = $this->mycred_type;
						$creds = $this->prefs['creds'][$key];
						$log = $this->prefs['log'][$key];
						
						if ($val == $membership_id) {
							$response = $this->get_user_limit($limit, $user_id, $type);
							if ($response === true) {
								
								mycred_add('mycred_pmp_purchase_membership', $user_id, $creds, $log, $order_id, $ref_type, $type);
							} 
							break;
						} else if ($val == 999999) {
							$response = $this->get_user_limit($limit, $user_id, $type);
							if ($response === true) {
							
								mycred_add('mycred_pmp_purchase_membership', $user_id, $creds, $log, $order_id, $ref_type, $type);
							} 
						}
					}
				}
			
		}

		public function award_points_after_successful_checkout($user_id, $order) {

		    // Only proceed if we have valid data and successful checkout
		    if (!$user_id || !$order) {
		        return;
		    }
		    
		    // Check if checkout was successful
		    if (isset($order->status) && $order->status !== 'success') {
		        return;
		    }

		    if ( $order->is_renewal()) {
					return;
			}
		    
		    // For free memberships, there might not be an order object
		    if (!$order && !empty($_REQUEST['level'])) {
		        $level_id = intval($_REQUEST['level']);
		        if ($level_id > 0) {
		            $this->award_points_after_membership_change($level_id, $user_id);
		        }
		        return;
		    }
		    
		    $level_id = $order->membership_id;
		    $this->award_points_after_membership_change($level_id, $user_id);

		}

	    public function award_points_on_level_change($level_id, $user_id, $old_level_ids) {
	    	
		    // Only award points if user is getting a new level (not cancelling)
		    if (!$level_id || !$user_id) {
		        return;
		    }
		    
		    // Check if this is a new membership (not just a level change)
		    if (!empty($old_level_ids) && in_array($level_id, $old_level_ids)) {
		        return; // Same level, don't award again
		    }
		    
		    // Add a small delay to ensure this runs after checkout
		    if (did_action('pmpro_after_checkout')) {
		        return; // Already handled by checkout hook
		    }
		    
		    // Check if there's a recent successful order for this user/level
		    $recent_order = $this->get_recent_successful_order($user_id, $level_id);
		    if ($recent_order) {
		        $this->award_points_after_membership_change($level_id, $user_id);
		    }

		}

		private function get_recent_successful_order($user_id, $level_id) {

		    global $wpdb;
		    
		    $order = $wpdb->get_row($wpdb->prepare("
		        SELECT * FROM {$wpdb->pmpro_membership_orders} 
		        WHERE user_id = %d 
		        AND membership_id = %d 
		        AND status = 'success' 
		        AND timestamp >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
		        ORDER BY timestamp DESC 
		        LIMIT 1
		    ", $user_id, $level_id));
		    
		    return $order;
		    
		}

		public function award_points_after_membership_change($level_id, $user_id) {

		    if (!$user_id || !$level_id) return;
		    
		    $membership_id = $level_id;
		    
		    // Enhanced duplicate prevention - check within last 24 hours
		    $meta_key = '_mycred_purchase_award_' . $membership_id . '_' . $this->mycred_type;

		    if ( $this->core->has_entry( 'mycred_pmp_purchase_membership', $membership_id, $user_id ) ) {
		    	return;
		    }
		    
		    $pmp_form_id = $this->prefs['pmp_form_id'];
		    $ref_type = array('ref_type' => 'post');
		    
		    if (!empty($pmp_form_id)) {
		        foreach ($pmp_form_id as $key => $val) {
		            $limit = $this->prefs['limit'][$key];
		            $type = $this->mycred_type;
		            $creds = $this->prefs['creds'][$key];
		            $log = $this->prefs['log'][$key];
		            
		            if ($val == $membership_id) {
		                $response = $this->get_user_limit($limit, $user_id, $type);
		                if ($response === true) {
		                    mycred_add('mycred_pmp_purchase_membership', $user_id, $creds, $log, $membership_id, $ref_type, $type);
		                    // Store timestamp instead of just 1
		                    update_user_meta($user_id, $meta_key, time());
		                }
		                break; // Found specific match, no need to check 999999
		            } else if ($val == 999999) {
		                $response = $this->get_user_limit($limit, $user_id, $type);
		                if ($response === true) {
		                    mycred_add('mycred_pmp_purchase_membership', $user_id, $creds, $log, $membership_id, $ref_type, $type);
		                    // Store timestamp instead of just 1
		                    update_user_meta($user_id, $meta_key, time());
		                }
		            }
		        }
		    }
		} 

		public function handle_order_status_change($order) {

		    if ( empty( $order->status ) || $order->status !== 'success' ) {
		        return;
		    }

		    if ( $order->is_renewal()) {
					return;
			}

		    $user_id = $order->user_id;

		    $level = $order->getMembershipLevel();
    
		    if ( empty( $level ) ) {
		        return;
		    }

		    $membership_id = $level->id;
		    $order_id = $order->id;

			$meta_key = '_mycred_purchase_award_' . $membership_id . '_' . $this->mycred_type;

		    if ( $this->core->has_entry( 'mycred_pmp_purchase_membership', $order_id, $user_id ) ) {
			  return;
			}

		    $pmp_form_id = $this->prefs['pmp_form_id'];
		    $ref_type = array('ref_type' => 'post');

		    if (!empty($pmp_form_id)) {
		        foreach ($pmp_form_id as $key => $val) {
		            $limit = $this->prefs['limit'][$key];
		            $type = $this->mycred_type;
		            $creds = $this->prefs['creds'][$key];
		            $log = $this->prefs['log'][$key];
		            
		            if ($val == $membership_id) {
		                $response = $this->get_user_limit($limit, $user_id, $type);
		                if ($response === true) {
		                  
		                    mycred_add('mycred_pmp_purchase_membership', $user_id, $creds, $log, $order_id, $ref_type, $type);
		                    // Store timestamp instead of just 1
		                    update_user_meta($user_id, $meta_key, time());
		                } 
		                break; // Found specific match, no need to check 999999
		            } else if ($val == 999999) {
		                $response = $this->get_user_limit($limit, $user_id, $type);
		                if ($response === true) {
		                    
		                    mycred_add('mycred_pmp_purchase_membership', $user_id, $creds, $log, $order_id, $ref_type, $type);
		                    // Store timestamp instead of just 1
		                    update_user_meta($user_id, $meta_key, time());
		                } 
		            }
		        }
		    }
		    
		    // If order status changed to failed/cancelled, consider removing points
		    if (in_array($order->status, array('cancelled', 'error', 'refunded', 'token'))) {
		        $meta_key = '_mycred_purchase_award_' . $order->membership_id . '_' . $this->mycred_type;
		        $award_time = get_user_meta($order->user_id, $meta_key, true);
		        
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
			if( $period == 'm' )
				$date_to_check = 'thismonth';
			else if( $period == 'w' )
				$date_to_check = 'thisweek';
			else if( $period == 'd' )
				$date_to_check = 'today';
			else if( $period == 't' )
				$date_to_check = 'total';
			else // when no limit set
 				return true;
			
			$args = array(
				'ref' => array('ids' => 'mycred_pmp_purchase_membership','compare' => '='),
				'user_id'   => $user_id,
				'ctype'     => $ctype,
				'date'     => $date_to_check,
			);
			$log  = new myCRED_Query_Log( $args );
			$used_limit = $log->num_rows;
			
			if( $used_limit >= $time )
				return false;
			
			return true;
			
		}
		
		/**
		* Preference for purchase membership hook
		**/
		public function preferences() {
			$prefs = $this->prefs;
			if ( isset($prefs['creds']) && count( $prefs['creds'] ) > 0 ) {
				$hooks = myCred_pmp_arrange_data( $prefs );
				myCred_pmp_hook_setting( $hooks, $this );
			}
			else {
				$default_data = array(
					array(
						'creds' => 10,
						'limit' => 'x',
						'log' => '%plural% for new purchase membership',
						'pmp_form_id' => '0',
					)
				);
				myCred_pmp_hook_setting( $default_data, $this );
			}

		}

	   /**
	   * Sanitize Preferences
	   */
		public function sanitise_preferences( $data ) {
			
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
						$new_data[$data_key][$key] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for new purchase membership';
					}
					else if ( $data_key == 'pmp_form_id' ) {
						$new_data[$data_key][$key] = ( !empty( $value ) ) ? intval( $value ) : 0;
					}
				}
			} 
			return $new_data;
		}
	}
endif;