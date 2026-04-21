<?php
if ( ! defined( 'BP_CHARGES_VERSION' ) ) exit;

/**
 * Profile Sale Stats
 * @version 1.0
 */
if ( ! function_exists( 'mycred_bpc_get_profile_sale_stats' ) ) :
	function mycred_bpc_get_profile_sale_stats( $user_id = NULL, $settings = array() ) {

		$stats = mycred_get_user_meta( $user_id, '_profile_sale_stats', '', true );
		if ( $stats == '' ) {

			global $wpdb;

			$mycred     = mycred();
			$mycred_log = $mycred->log_table;

			$stats       = array();
			$total_sales = $wpdb->get_var( $wpdb->prepare( "
				SELECT SUM( creds ) 
				FROM {$mycred_log} 
				WHERE ref = %s 
					AND user_id = %d;", 'profile_access_sale', $user_id ) );

			if ( $total_sales === NULL )
				$stats['total_sales'] = 0;
			else
				$stats['total_sales'] = $total_sales;

			$total_sales_month = $wpdb->get_var( $wpdb->prepare( "
				SELECT SUM( creds ) 
				FROM {$mycred_log} 
				WHERE ref = %s 
					AND user_id = %d 
					AND time > %d;", 'profile_access_sale', $user_id, strtotime( 'first day of this month' ) ) );

			if ( $total_sales_month === NULL )
				$stats['total_sales_month'] = 0;
			else
				$stats['total_sales_month'] = $total_sales_month;

			$total_buyers = $wpdb->get_var( $wpdb->prepare( "
				SELECT DISTINCT COUNT( user_id ) 
				FROM {$mycred_log} 
				WHERE ref = %s 
					AND ref_id = %d;", 'profile_access', $user_id ) );

			if ( $total_buyers === NULL )
				$stats['total_buyers'] = 0;
			else
				$stats['total_buyers'] = $total_buyers;

			if ( $settings['expire'] > 0 ) {

				$current_access = $wpdb->get_var( $wpdb->prepare( "
					SELECT DISTINCT COUNT( user_id ) 
					FROM {$mycred_log} 
					WHERE ref = %s 
						AND ref_id = %d
						AND time > %d;", 'profile_access', $user_id, strtotime( '-' . $settings['expire'] . ' days' ) ) );

				if ( $current_access === NULL )
					$stats['current_access'] = 0;
				else
					$stats['current_access'] = $current_access;

				$top_buyer_id = $wpdb->get_var( $wpdb->prepare( "
					SELECT user_id 
					FROM {$mycred_log} 
					WHERE ref = %s 
						AND ref_id = %d
					GROUP BY user_id 
					ORDER BY COUNT( * ) DESC 
					LIMIT 0,1;", 'profile_access', $user_id ) );

				if ( $top_buyer_id !== NULL )
					$stats['top_buyer'] = get_userdata( $top_buyer_id );
				else
					$stats['top_buyer'] = NULL;

			}
			else {
				$stats['current_access'] = $stats['total_buyers'];
				$stats['top_buyer']      = NULL;
			}

			mycred_update_user_meta( $user_id, '_profile_sale_stats', '', $stats );

		}

		return $stats;

	}
endif;

/**
 * Last Purchase
 * @version 1.0
 */
if ( ! function_exists( 'get_last_time_profile_was_purchased' ) ) :
	function get_last_time_profile_was_purchased( $buyer_id = NULL, $profile_id = NULL ) {

		global $wpdb;

		$mycred     = mycred();
		$mycred_log = $mycred->log_table;

		return $wpdb->get_var( $wpdb->prepare( "
			SELECT time 
			FROM {$mycred_log} 
			WHERE ref = %s 
				AND user_id = %d 
				AND ref_id = %d 
			ORDER BY time DESC 
			LIMIT 0,1;", 'profile_access', $buyer_id, $profile_id ) );

	}
endif;

/**
 * Display Profile Purchase Form
 * @version 1.0.1
 */
if ( ! function_exists( 'display_profile_purchase_form' ) ) :
	function display_profile_purchase_form( $type = 'profile', $object_id = NULL ) {

		$cui  = get_current_user_id();
		$core = get_option( 'mycred_pref_buddypress_charges' );

		if ( ! is_user_logged_in() ) {

			if ( $type == 'profile' )
				$message = $core['charge_prefs']['view_profile']['non_member_template'];
			else
				$message = $core['charge_prefs']['join_group']['non_member_template'];

			$message = stripslashes_deep( $message );

			echo do_shortcode( wpautop( wptexturize( $message ) ) );

			return;

		}

		if ( $type == 'profile' ) {

			if ( ! isset( $core['charge_prefs']['view_profile']['default_setup'] ) ) return;

			$bp_charge  = new myCRED_BP_Charge_View_Profile( $core['charge_prefs'] );

			$profile_id = $object_id;
			$settings   = $bp_charge->users_profile_setup( $object_id );
			$balance    = $bp_charge->core->get_users_balance( $cui, $core['charge_prefs']['view_profile']['ctype'] );

			if ( $balance < $settings['price'] ) {
				$message = $bp_charge->core->template_tags_amount( $settings['insufficient'], $settings['price'] );
				$message = $bp_charge->core->template_tags_user( $message, $profile_id );
				$message = str_replace( '%your_balance%', $bp_charge->core->format_creds( $balance ), $message );
				$message = apply_filters( 'mycred_bpc_insufficient_template', $message, $profile_id, $bp_charge );
			}
			else {
				$message = $bp_charge->core->template_tags_amount( $settings['content'], $settings['price'] );
				$message = $bp_charge->core->template_tags_user( $message, $profile_id );
				$message = str_replace( '%button%', '<a href="' . $settings['buy_url'] . '" class="btn btn-lg btn-primary button button-large button-primary">' . __( 'Buy Now', 'bp_charge' ) . '</a>', $message );
				$message = apply_filters( 'mycred_bpc_sale_template', $message, $profile_id, $bp_charge );
			}

			$_message = str_replace( '%buyers%', '', $message );
			if ( $_message != $message ) {

				$output  = '';
				$avatars = get_profile_buyer_avatars( $profile_id, $core['charge_prefs']['view_profile']['ctype'], $settings['expire'], 25 );
				if ( ! empty( $avatars ) ) {
					foreach ( $avatars as $avatar ) {
						$output .= '<div class="buyer-avatar">' . $avatar . '</div>';
					}
				}

				$message = str_replace( '%buyers%',      $output, $message );
				$message = str_replace( '%buyer_count%', count( $avatars ), $message );

			}

		}

		elseif ( $type == 'group' ) {

			if ( ! isset( $core['charge_prefs']['join_group']['default_setup'] ) ) return;

			$bp_charge = new myCRED_BP_Charge_Join_Group( $core['charge_prefs'] );

			$group_id  = $object_id;
			$settings  = $bp_charge->group_setup( $object_id );
			$balance   = $bp_charge->core->get_users_balance( $cui, $core['charge_prefs']['join_group']['ctype'] );

			if ( $balance < $settings['price'] ) {
				$message = $bp_charge->core->template_tags_amount( $settings['insufficient'], $settings['price'] );
				$message = $bp_charge->core->template_tags_user( $message, $group_id );
				$message = str_replace( '%your_balance%', $bp_charge->core->format_creds( $balance ), $message );
				$message = apply_filters( 'mycred_bpc_insufficient_template', $message, $group_id, $bp_charge );
			}
			else {
				$message = $bp_charge->core->template_tags_amount( $settings['content'], $settings['price'] );
				$message = str_replace( '%button%', bp_get_group_join_button( false ), $message );
				$message = apply_filters( 'mycred_bpc_sale_template', $message, $group_id, $bp_charge );
			}

		}
		else return;

		if ( array_key_exists( 'expire', $settings ) ) {

			if ( $settings['expire'] > 0 )
				$message = str_replace( '%expires%', sprintf( _n( 'After 1 Day', 'After %d Days', $settings['expire'], 'bp_charge' ), $settings['expire'] ), $message );

			else
				$message = str_replace( '%expires%', __( 'Never', 'bp_charge' ), $message );

		}
		else {
			$message = str_replace( '%expires%', __( 'Never', 'bp_charge' ), $message );
		}

		echo do_shortcode( wpautop( wptexturize( $message ) ) );

	}
endif;

/**
 * Get Profile Buyer's Avatars
 * @version 1.0
 */
if ( ! function_exists( 'get_profile_buyer_avatars' ) ) :
	function get_profile_buyer_avatars( $profile_id, $type = 'mycred_default', $expire = 0, $number = 25, $size = 64 ) {

		global $wpdb;

		$mycred = mycred( $type );

		if ( $expire > 0 )
			$users = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT log.user_id  
				FROM {$mycred->log_table} log 
				WHERE log.ref = %s 
				AND log.ref_id = %d 
				AND log.time > %d 
				AND log.ctype = %s 
				ORDER BY log.time ASC 
				LIMIT 0,%d;", 'profile_access', $profile_id, strtotime( '-' . $expire . ' days' ), $type, $number ) );

		else
			$users = $wpdb->get_col( $wpdb->prepare( "
				SELECT DISTINCT user_id  
				FROM {$mycred->log_table} 
				WHERE ref = %s 
				AND ref_id = %d 
				AND ctype = %s 
				ORDER BY time ASC 
				LIMIT 0,%d;", 'profile_access', $profile_id, $type, $number ) );

		if ( empty( $users ) || $users === NULL ) return array();

		$avatars = array();
		foreach ( $users as $user_id ) {

			$avatar    = bp_core_fetch_avatar( array( 'item_id' => $user_id, 'type' => 'thumb', 'class' => 'bp-charge-avatar', 'width' => $size, 'height' => $size ) );
			$avatar    = apply_filters( 'mycred_bp_charges_avatar', $avatar, $user_id, $size );
			$avatars[] = '<a href="' . bp_core_get_user_domain( $user_id ) . '">' . $avatar . '</a>';

		}

		return $avatars;

	}
endif;
