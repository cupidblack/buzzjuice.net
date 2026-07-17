<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
* myCRED_Addons_Module class
**/
if ( ! class_exists( 'myCRED_ZA_Hook' ) ) :
	class myCRED_ZA_Hook extends myCRED_Hook {

		public $abc = 0;
		/**
		* Construct
		*/
		function __construct( $hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY ) {

			parent::__construct( array(
				'id'       => 'mycred_zoom',
				'defaults' => array(
					'creds'    => array(),
					'limit'    => array(),
					'log'      => array(),
					'type'     => $type,
					'zoom_id'  => array(),
				)
			), $hook_prefs, $type );
		}

		/**
		* Run Function
		**/
		public function run() {
			add_action('vczoom_meeting_shortcode_join_links', array( $this, 'myCred_show_meeting_points' ), 10, 1);
			add_action('vczoom_meeting_join_links', array( $this, 'myCred_show_meeting_points_right' ), 10, 1);
			
			add_action( 'wp_ajax_myCred_za_save_entry', array( $this, 'myCred_za_save_entry' ));
			add_action( 'wp_ajax_nopriv_myCred_za_save_entry', array( $this, 'myCred_za_save_entry' ));
		}
		
		/**
		* myCred show meeting points 
		**/
		public function myCred_show_meeting_points( $data ) {
			global $abc;
			if (is_object ($data)) {
				$meeting_id = $data->id; 
			} else {
$meeting_id = $data;
			}
			$translated =''; 
			$post_id = $this->myCred_get_post_id($meeting_id);
			$zoom_id = $this->prefs['zoom_id'];
			
			if ($zoom_id != false) {
				if ($abc == 0 ) {
						$translated.='Join this meeting and get';
				}
					$abc = 1;
				if ( in_array( $post_id, $zoom_id ) ) {
					if (!empty($zoom_id)) :
						foreach ($zoom_id as $key => $val) :
							if ($val == $post_id) : 
								$postidkey = $key;
								if (is_user_logged_in()) {
									$user = wp_get_current_user();
									$user_id = $user->ID;
									$limit = $this->prefs['limit'][ $postidkey ];
									$ctype = $this->prefs['type'];
									$type_name = $this->get_type_lable($ctype);
									$response = $this->get_user_limit($limit, $user_id, $ctype);
									if ($response == true) {
										$translated.= ' ' . $this->prefs['creds'][ $postidkey ] . ' ' . $type_name . ', ';
									}
								}
							endif;
						endforeach;
					endif; 
					   echo "<span class='zoom_text'>" . esc_html($translated) . '</span>';
				} else {
					
					if (!empty($zoom_id)) :
						foreach ($zoom_id as $key => $val) :
							if ($val == 999999) { 
								$postidkey = $key;
								if (is_user_logged_in()) {
									$user = wp_get_current_user();
									$user_id = $user->ID;
									$limit = $this->prefs['limit'][ $postidkey ];
									$ctype = $this->prefs['type'];
									$type_name = $this->get_type_lable($ctype);
									$response = $this->get_user_limit($limit, $user_id, $ctype);
									if ($response == true) {
										$translated.= ' ' . $this->prefs['creds'][ $postidkey ] . ' ' . $type_name . ', ';
									}
								}
							}
						endforeach;
					endif;
					if ($translated =='Join this meeting and get') {
						echo "<span class='zoom_text' style='display: none;'></span>";
					} else {
echo "<span class='zoom_text'>" . esc_html($translated) . '</span>';
					}    
					
					
					
				}
				echo '<input type="hidden" name="post_id" value="' . esc_attr($post_id) . '" id="post_id"><input type="hidden" name="meeting_id" value="' . esc_attr($meeting_id) . '" id="meeting_id">';
			} 
		}
		
		/**
		* Get Type Lable
		**/
		public function get_type_lable( $type ) {
			$mycred = strtolower(mycred_get_point_type_name($type, false)); 
			return $mycred; 
		}
		
		/**
		* $limit = 2/d , 3/w, 5/m
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
					'ids' => 'mycred_zoom',
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
		*  myCred get post id
		**/
		public function myCred_get_post_id( $meeting_id ) {
			
			global $wpdb;
			$results = $wpdb->get_results( "select post_id, meta_key from $wpdb->postmeta where meta_value = '$meeting_id'", ARRAY_A );
			if (!empty($results)) {
				return $post_id = $results[0]['post_id'];
			}
			return false;
		}
		
		/**
		* myCred Post Title By id
		**/
		public function myCred_get_post_title( $post_id ) {
			$title = get_the_title($post_id);
			if (!empty($title)) {
				return $title;
			}
			return false;
		}
		
		/** 
		*   myCred show meeting points right
		**/
		public function myCred_show_meeting_points_right( $data ) {
			$meeting_id = $data->id;
			$this->myCred_show_meeting_points($meeting_id);
		}
		
		/** 
		*   myCred save entry
		**/
		public function myCred_za_save_entry() {
			$post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
			$meeting_id = isset($_POST['meeting_id']) ? (int) $_POST['meeting_id'] : 0;
			$zoom_id = $this->prefs['zoom_id'];
			// Make sure user is not excluded
			if (is_user_logged_in()) {
				$user = wp_get_current_user();
				$Post_title = $this->myCred_get_post_title($post_id);
				$user_id = $user->ID;
				if ( ! $this->core->exclude_user( $user_id ) ) {
					if (!empty($zoom_id)) :
						foreach ($zoom_id as $key => $val) :
							   $postidkey = $key;
							if ($val == $post_id) {
								
								$ref_type  = array( 'ref_type' => 'post' );
								// Make sure this is unique
								//if ( $this->core->has_entry('mycred_zoom', $post_id, $user_id, $ref_type,$this->prefs['type'])) return;
								// Check hook limit
								$limit = $this->prefs['limit'][ $postidkey ];
								$ctype = $this->prefs['type'];
								$response = $this->get_user_limit($limit, $user_id, $ctype);
								if ($response == true) {  
									mycred_add('mycred_zoom', $user_id, $this->prefs['creds'][ $postidkey ], $this->prefs['log'][ $postidkey ] . ' ' . $Post_title, $post_id, $ref_type, $this->prefs['type']);
								}
							} else if ($val == 999999) {
									$limit = $this->prefs['limit'][ $postidkey ];
									$ctype = $this->prefs['type'];
									$response = $this->get_user_limit($limit, $user_id, $ctype);
								if ($response == true) {
									mycred_add('mycred_zoom', $user_id, $this->prefs['creds'][ $postidkey ], $this->prefs['log'][ $postidkey ] . ' ' . $Post_title, $post_id, $ref_type, $this->prefs['type']);
								}
								
							} 
						endforeach;
					endif; 
				}
			}
		} 
		
		/**
		* Preference for give wp hook
		**/
		public function preferences() {
			
			$prefs = $this->prefs;
			if ( isset($prefs['creds']) && count( $prefs['creds'] ) > 0 ) {
				$hooks = myCred_ZA_Arrange_Data( $prefs );
				myCred_ZA_Hook_Setting( $hooks, $this );
			} else {
				$default_data = array(
					array(
						'creds' => 10,
						'limit' => 'x',
						'log' => '%plural% for joining zoom meeting',
						'zoom_id' => '0',
					)
				);
				myCred_ZA_Hook_Setting( $default_data, $this );
			}
		}

	   /**
	   * Sanitize Preferences
	   */
		public function sanitise_preferences( $data ) {
			
			foreach ( $data as $data_key => $data_value ) {
				foreach ( $data_value as $key => $value) {
					if ( $data_key == 'creds' ) {
						$new_data[ $data_key ][ $key ] = ( !empty( $value ) ) ? floatval( $value ) : 10;
					} else if ( $data_key == 'limit' ) {
						$limit = sanitize_text_field( $data[ $data_key ][ $key ]);
						if ( $limit == '' ) {
$limit = 0;
						}
						$new_data[ $data_key ][ $key ] = $limit . '/' . $data['limit_by'][ $key ];
					} else if ( $data_key == 'log' ) {
						$new_data[ $data_key ][ $key ] = ( !empty( $value ) ) ? sanitize_text_field( $value ) : '%plural% for joining zoom meeting';
					} else if ( $data_key == 'zoom_id' ) {
						$new_data[ $data_key ][ $key ] = ( !empty( $value ) ) ? intval( $value ) : 0;
					}
					
				}
			} 
			return $new_data;
		}
	}
endif;
