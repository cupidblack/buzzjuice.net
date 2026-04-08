<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'myCredZapierAPI' ) ) :
	class myCredZapierAPI {

		private static $_instance;
	
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	
		public function register_routes() {
			register_rest_route( 'zapier', '/v1/auth', array(
				'methods'  => 'POST',
				'callback' => array( $this, 'zapier_auth' )
			));
		
			register_rest_route( 'zapier', '/v1/earned-logs', array(
				'methods'  => 'POST',
				'callback' => array( $this, 'earned_logs' )
			));
		
			register_rest_route( 'zapier', '/v1/deducted-logs', array(
				'methods'  => 'POST',
				'callback' => array( $this, 'deducted_logs' )
			));
		
			register_rest_route( 'zapier', '/v1/earned-badges', array(
				'methods'  => 'POST',
				'callback' => array( $this, 'earned_badges' )
			));
		
			register_rest_route( 'zapier', '/v1/earned-ranks', array(
				'methods'  => 'POST',
				'callback' => array( $this, 'earned_ranks' )
			));
		
			register_rest_route( 'zapier', '/v1/lost-ranks', array(
				'methods'  => 'POST',
				'callback' => array( $this, 'lost_ranks' )
			));
		}
	
		public function zapier_auth( $request ) {
			$response = new WP_REST_Response( $request );
		
			$api_key = $request->get_param( 'api_key' );
		
			if ( $api_key == $this->get_api_key() ) {
			return new WP_REST_Response( true, 200 );
			}
		
			if ( $api_key == null ) {
			return new WP_Error( 400, __( 'Required Parameter Missing', 'mycred-toolkit' ), 'api_key required' );
			}
		
			if ( $api_key != $this->get_api_key() ) {
			return new WP_Error( 400, __( 'Invalid API Key', 'mycred-toolkit' ), 'invalid api_key' );
			}
		}
	
		public function earned_logs( $request ) {

			$response = new WP_REST_Response( $request );
		
			$api_key = $request->get_param( 'api_key' );
		
			if ( $api_key == null ) {
			return new WP_Error( 400, __( 'Required Parameter Missing', 'mycred-toolkit' ), 'api_key required' );
			}
		
			if ( $api_key != $this->get_api_key() ) {
			return new WP_Error( 400, __( 'Invalid API Key', 'mycred-toolkit' ), 'invalid api_key' );
			}
		
			if ( $api_key == $this->get_api_key() ) {
				$args = array(
					'number' => 10,
					'orderby' => 'id',
					'order'   => 'DESC',
					'amount' => array(
						'num'     => 0,
						'compare' => '>'
					),
				);

				$log = new myCRED_Query_Log( $args );

				$logs = $log->results;
			
				foreach ( $logs as $log ) {
					$point_type = '';
				
					if ( $log->creds > 1 ) {
					$point_type = mycred_get_point_type( $log->entry )->plural;
					} else {
$point_type = mycred_get_point_type( $log->entry )->singular;
					}
				
					$log->ctype = $point_type;
				
					$user_data = get_userdata( $log->user_id );
				
					$log->user_name = $user_data->display_name;
				
					$log->user_email = $user_data->user_email;
				
					$log->time = date( 'd-m-Y h:i:s', $log->time );

					unset( $log->time );

					$log->entry = str_replace( '%plural%', 'default_type', $point_type );
				}
			
				return $logs;
			}
		}
	
		public function deducted_logs( $request ) {

			$response = new WP_REST_Response( $request );
		
			$api_key = $request->get_param( 'api_key' );
		
			if ( $api_key == null ) {
			return new WP_Error( 400, __( 'Required Parameter Missing', 'mycred-toolkit' ), 'api_key required' );
			}
		
			if ( $api_key != $this->get_api_key() ) {
			return new WP_Error( 400, __( 'Invalid API Key', 'mycred-toolkit' ), 'invalid api_key' );
			}
		
			if ( $api_key == $this->get_api_key() ) {
				$args = array(
					'number' => 10,
					'orderby' => 'id',
					'order'   => 'DESC',
					'amount' => array(
						'num'     => 0,
						'compare' => '<'
					),
				);

				$log = new myCRED_Query_Log( $args );

				$logs = $log->results;
			
				foreach ( $logs as $log ) {
					$point_type = '';
				
					if ( $log->creds < -1 ) {
					$point_type = mycred_get_point_type( $log->entry )->plural;
					} else {
$point_type = mycred_get_point_type( $log->entry )->singular;
					}
				
					$log->ctype = $point_type;
				
					$user_data = get_userdata( $log->user_id );
				
					$log->user_name = $user_data->display_name;
				
					$log->user_email = $user_data->user_email;
				
					$log->time = date( 'd-m-Y h:i:s', $log->time );

					unset( $log->time );

					$log->entry = str_replace( '%plural%', 'default_type', $point_type );
				}
			
				return $logs;
			}
		}
	
		public function earned_badges( $request ) {

			$response = new WP_REST_Response( $request );
		
			$api_key = $request->get_param( 'api_key' );
		
			if ( $api_key == null ) {
			return new WP_Error( 400, __( 'Required Parameter Missing', 'mycred-toolkit' ), 'api_key required' );
			}
		
			if ( $api_key != $this->get_api_key() ) {
			return new WP_Error( 400, __( 'Invalid API Key', 'mycred-toolkit' ), 'invalid api_key' );
			}
		
			if ( $api_key == $this->get_api_key() ) {

				global $wpdb;

				$tbl_zapier = $wpdb->prefix . 'mycred_zapier';
			
				$logs = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$tbl_zapier} WHERE ref = %s ORDER BY id;", 'badge_earned' )
				);

				$users  = array(); 
				$badges = array(); 

				$earned_badges = array();
			
				foreach ( $logs as $log ) {

					if ( empty( $users[ $log->user_id ] ) ) { 
						$users[ $log->user_id ] = get_userdata( $log->user_id );
					}

					if ( empty( $badges[ $log->ref_id ] ) ) {
						$badges[ $log->ref_id ] = mycred_get_badge( $log->ref_id );
					}

					array_push( $earned_badges, array(
						'id'          => $log->id,
						'badge_id'    => $log->ref_id,
						'badge_title' => $badges[ $log->ref_id ]->title,
						'user_name'   => $users[ $log->user_id ]->display_name,
						'user_email'  => $users[ $log->user_id ]->user_email
					) );    

				}
			
				return $earned_badges;

			}
		}
	
		public function earned_ranks( $request ) {

			$response = new WP_REST_Response( $request );
		
			$api_key = $request->get_param( 'api_key' );
		
			if ( $api_key == null ) {
			return new WP_Error( 400, __( 'Required Parameter Missing', 'mycred-toolkit' ), 'api_key required' );
			}
		
			if ( $api_key != $this->get_api_key() ) {
			return new WP_Error( 400, __( 'Invalid API Key', 'mycred-toolkit' ), 'invalid api_key' );
			}
		
			if ( $api_key == $this->get_api_key() ) {
			
				global $wpdb;

				$tbl_zapier = $wpdb->prefix . 'mycred_zapier';
			
				$logs = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$tbl_zapier} WHERE ref = %s ORDER BY id;", 'rank_promoted' )
				);

				$users = array(); 
				$ranks = array(); 

				$rank_promotions = array();
			
				foreach ( $logs as $log ) {

					if ( empty( $users[ $log->user_id ] ) ) { 
						$users[ $log->user_id ] = get_userdata( $log->user_id );
					}

					if ( empty( $ranks[ $log->ref_id ] ) ) {
						$ranks[ $log->ref_id ] = mycred_get_rank( $log->ref_id );
					}

					array_push( $rank_promotions, array(
						'id'         => $log->id,
						'rank_id'    => $log->ref_id,
						'rank_title' => $ranks[ $log->ref_id ]->title,
						'user_name'  => $users[ $log->user_id ]->display_name,
						'user_email' => $users[ $log->user_id ]->user_email
					) );    

				}
			
				return $rank_promotions;

			}
		}
	
		public function lost_ranks( $request ) {

			$response = new WP_REST_Response( $request );
		
			$api_key = $request->get_param( 'api_key' );
		
			if ( $api_key == null ) {
			return new WP_Error( 400, __( 'Required Parameter Missing', 'mycred-toolkit' ), 'api_key required' );
			}
		
			if ( $api_key != $this->get_api_key() ) {
			return new WP_Error( 400, __( 'Invalid API Key', 'mycred-toolkit' ), 'invalid api_key' );
			}
		
			if ( $api_key == $this->get_api_key() ) {

				global $wpdb;

				$tbl_zapier = $wpdb->prefix . 'mycred_zapier';
			
				$logs = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM {$tbl_zapier} WHERE ref = %s ORDER BY id;", 'rank_demoted' )
				);

				$users = array(); 
				$ranks = array(); 

				$rank_demotions = array();
			
				foreach ( $logs as $log ) {

					if ( empty( $users[ $log->user_id ] ) ) { 
						$users[ $log->user_id ] = get_userdata( $log->user_id );
					}

					if ( empty( $ranks[ $log->ref_id ] ) ) {
						$ranks[ $log->ref_id ] = mycred_get_rank( $log->ref_id );
					}

					array_push( $rank_demotions, array(
						'id'         => $log->id,
						'rank_id'    => $log->ref_id,
						'rank_title' => $ranks[ $log->ref_id ]->title,
						'user_name'  => $users[ $log->user_id ]->display_name,
						'user_email' => $users[ $log->user_id ]->user_email
					) );    

				}
			
				return $rank_demotions;

			}
		}
	
		private function get_api_key() {
			$mycred_prefs = get_option( 'mycred_pref_core' );
		
			return $mycred_prefs['zapier']['mycred_zapier_api_key'];
		}
	
		public static function get_instance() {
			if ( self::$_instance == null ) {
			self::$_instance = new self();
			}

			return self::$_instance;
		}
	}
endif;

myCredZapierAPI::get_instance();
