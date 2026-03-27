<?php


/**
 * myCRED Shortcode: mycred_my_ranks
 * Returns the given users ranks.
 * @see http://mycred.me/shortcodes/mycred_my_ranks/
 * @since 1.6
 * @version 1.2.1
 */
if ( ! function_exists( 'mycred_render_my_ranks_progress' ) ) :
	function mycred_render_my_ranks_progress( $atts, $content = '' ) {
		if ( ! class_exists( 'myCRED_Ranks_Module' ) ) return __( 'The rank add-on is not enabled!', 'mycred_pb' );
		extract( shortcode_atts( array(
			'user_id'    => 'current',
			'title' => 0,
			'ctype' => MYCRED_DEFAULT_TYPE_KEY,
			'animate' => 0,
			'bgcolor' => '#333',
			'show_rank_title' => 0,
			'show_labels' => 1,
			'show_logo'  => 1,
			'logo_size'  => 'post-thumbnail',
			'first'      => 'logo',
			'rank_id'      => '',
			'type'      => 'bar',
			'prefix_min'      => 'Min',
			'prefix_max'      => 'Max',
		), $atts ) );
		
		$types=array('bar','radial','stars');
		
		$type = in_array($type,$types) == false ? 'bar' : $type;
		if ($user_id == 'current' && ! is_user_logged_in()) return;

		if (empty($user_id))
			return;

		if($rank_id != ''){
			if(get_post_meta($rank_id,'ctype',true) != $ctype )
				return "No rank found.";
		}

		if ( ! mycred_have_ranks( $ctype ) ) {
				return 'No rank found.';
				
		}

		$user_id      =  $user_id != 'current' ? $user_id : mycred_get_user_id( $user_id );
		$mycred_type  = $ctype;
		$show         = array();
		$ranks        = array();

		// This user does not have any usable point types
		if ( empty( $mycred_type ) ) return;

		// Get the rank for each type


			$row  = array();
			
			if($rank_id !=''){
				$rank = mycred_get_rank($rank_id);
			}elseif($ctype != MYCRED_DEFAULT_TYPE_KEY){
				$rank = mycred_get_users_rank($user_id,$ctype);

			}else{

				$rank = mycred_get_my_rank();
			}

			if ($rank == false) {
				return "No rank found.";
			}

			if ( $rank !== false ) {

				if ( $show_logo == 1 && $rank->has_logo )
					$row[] = mycred_get_rank_logo( $rank->post_id, $logo_size );

				if ( $title == 1 )
					$row[] = $rank->title;
		
				if ( $first != 'logo' )
					$row = array_reverse( $row );

			}

			$mycred      = mycred( $ctype );
			// Ranks are based on a total
			if ( isset( $mycred->rank['base'] ) && $mycred->rank['base'] == 'total' )
			$users_balance = mycred_query_users_total( $user_id, $ctype );

			// Ranks are based on current balance
			else
			$users_balance = $mycred->get_users_balance( $user_id );

			// if(mycred_get_users_balance($user_id,$ctype) >= $rank->maximum ){
			if($rank->minimum !=0){
				$myBalance = $users_balance - $rank->minimum ;
				$rankBalance = $rank->maximum - $rank->minimum ;
				$progress = round(($myBalance/$rankBalance)*100);

				
			}else{
			
				$progress = round(($users_balance/$rank->maximum)*100);
			}
 			
 			if ( $progress > 100 || $users_balance == $rank->maximum )
				$progress = 100;

			if ( $progress < 0 || $users_balance == $rank->minimum )
				$progress = 0;



 			$progrssBar = mycred_get_progress_bar($progress,$show_rank_title,$show_labels,$rank,$bgcolor,$animate,$type,$prefix_min,$prefix_max);

			if ( ! empty( $row ) || empty( $row ) )
				$show[] = '<div class="mycred-my-rank ' . $mycred_type . '">' . implode( ' ', $row ) . ''.$progrssBar.'</div>';

			$ranks[] = $rank;


		if ( ! empty( $show ) )
			$content = '<div class="mycred-all-my-ranks">' . implode( ' ', $show ) . '</div>';

		return apply_filters( 'mycred_my_ranks_progress', $content, $user_id, $ranks );

	}
endif;



function mycred_get_progress_bar($progress,$show_rank_title,$show_labels,$rank_obj,$bgcolor,$animate,$type,$prefix_min,$prefix_max){
	$title = $show_rank_title == 0 ? '': $rank_obj->title; 
	$animation = $animate == 1 ? 'active' : '';

	$color = $bgcolor == '' ? 'blue' : $bgcolor; 
	$type = $type == '' ? 'bar' : $type; 

if ($type == 'bar') {
	# code...
	$progrssBar = '<div class="progress" id="mycred-progressbar-style">
  <div class="progress-bar   progress-bar-striped '.$animation.'" role="progressbar"
  aria-valuenow="'.$progress.'" aria-valuemin="0" aria-valuemax="100" style="width:'.$progress.'%;background-color:'.$color.';">
    '.$progress.'%'.$title.'
  </div>
</div>';
}

if ($type == 'radial') {
	# code...
	$progrssBar = '<div class="c100 p'.round((int)$progress).' '.$color.'">
                    <span>'.round((int)$progress).'%</span>
                    <div class="slice">
                        <div class="bar"></div>
                        <div class="fill"></div>
                    </div>
                </div>';

}

if ($type == 'stars') {
	$progrssBar = '<div class="star-ratings-css">
  <div class="star-ratings-css-top" style="width:'.round((int)$progress).'%;"><span>★</span><span>★</span><span>★</span><span>★</span><span>★</span></div>';
}



	if ($show_labels == 1) {
		$progressBar = $progrssBar.='<span class="min-num">'.$prefix_min.' =  '.$rank_obj->point_type->prefix.' '.$rank_obj->minimum.'</span><span class="max-num">'.$prefix_max.' = '.$rank_obj->point_type->prefix.' '.$rank_obj->maximum.'</span>';

	}

	return apply_filters('get_user_rank_progressbar',$progrssBar,$rank_obj);
}


/**
 * Shortcode: mycred_badges_progress
 * Allows you to show all published badges with progresses
 * @since 1.5
 * @version 1.1.1
 */
if ( ! function_exists( 'mycred_render_badges_progress' ) ) :
	function mycred_render_badges_progress( $atts, $template = '' ) {
		
		extract( shortcode_atts( array(
			'width'  => MYCRED_BADGE_WIDTH,
			'height' => MYCRED_BADGE_HEIGHT,
			'show_badge' => '',
			'animate' => '1',
			'bgcolor' => '#333',
			'type' => 'bars',
			'stripe' => '1'

		), $atts ) );

		$types=array('bars','radial');
		
		$type = in_array($type,$types) == false ? 'bars' : $type;
		
		$settings = array('animate' => $animate,'bgcolor' => $bgcolor,'type' => $type,'stripe' => $stripe);
	
		if (!is_user_logged_in())
			return '<h2>User must be login to see progress.</h2>';

		if($show_badge == ''){
			$all_badges = mycred_get_badge_ids();
		}else{
			$all_badges = array();
			array_push($all_badges,$show_badge );
		}

		if ( $template == '' )
			$template = '<div class="the-badge row"><div class="col-xs-12"><h3 class="badge-title">%badge_title% <div class="badge-images">%default_image% </div></h3><div class="badge-requirements">%requirements%</div></div></div>';

		$output = '<div id="mycred-all-badges">';

		if ( ! empty( $all_badges ) ) {

			foreach ( $all_badges as $badge_id ) {

				$badge               = mycred_get_badge( $badge_id, 0 );
				$badge->image_width  = $width;
				$badge->image_height = $height;
				

				$row = $template;
				$row = str_replace( '%badge_title%',   $badge->title,                                  $row );
				$row = str_replace( '%requirements%',  mycred_display_badge_requirement_progress( $badge_id,$settings ), $row );
				$row = str_replace( '%default_image%', $badge->main_image,                             $row );
				

				$output .= apply_filters( 'mycred_badges_badge_progress', $row, $badge );


			}

		}

		$output .= '</div>';

		return apply_filters( 'mycred_badges_progress', $output );

	}
endif;


/**
 * Display Badge Requirements and append progressbar with each requiremnt
 * Returns the badge requirements as a string in a readable format.
 * @since 1.5
 * @version 1.2.2
 */
if ( ! function_exists( 'mycred_display_badge_requirement_progress' ) ) :
	function mycred_display_badge_requirement_progress( $badge_id = NULL, $settings = NULL ) {

		$levels = mycred_get_badge_levels( $badge_id );

		if ( empty( $levels ) ) {

			$reply = '-';

		}
		else {

			$point_types = mycred_get_types( true );
			$references  = mycred_get_all_references();
			$req_count   = count( $levels[0]['requires'] );

			// Get the requirements for the first level
			$base_requirements = array();
			foreach ( $levels[0]['requires'] as $requirement_row => $requirement ) {

				if ( $requirement['type'] == '' )
					$requirement['type'] = MYCRED_DEFAULT_TYPE_KEY;

				if ( ! array_key_exists( $requirement['type'], $point_types ) )
					continue;

				if ( ! array_key_exists( $requirement['reference'], $references ) )
					$reference = '-';
				else
					$reference = $references[ $requirement['reference'] ];

				$base_requirements[ $requirement_row ] = array(
					'type'   => $requirement['type'],
					'ref'    => $reference,
					'amount' => $requirement['amount'],
					'by'     => $requirement['by']
				);

			}


			// Loop through each level
			$output = array();
			foreach ( $levels as $level => $setup ) {
				//this function develop progress bars for req and levels
				$progress = mycred_badge_progress_reached(get_current_user_id(),$badge_id,$level,$settings);
				//req progress bars array
				$progressbars = $progress[0];
				// level progressbar array
				$levelprogress = $progress[1];

				$badge = new myCRED_Badge( $badge_id,$level  );
				$level_label = '<strong>' . sprintf( __( 'Level %s', 'mycred' ), ( $level + 1 ) ) . ':'.$badge->level_image.'  '.$progress[1].'</strong>';
				
				
				if ( $levels[ $level ]['label'] != '' ){
					
					$level_label = '<strong>' . $levels[0]['label'] . ':'.$badge->level_image.' '.$progress[1].'</strong>';
				}

				// Construct requirements to be used in an unorganized list.
				$level_req = array();
				$key=0;
				foreach ( $setup['requires'] as $requirement_row => $requirement ) {
					
					$level_value = $requirement['amount'];
					$requirement = $base_requirements[ $requirement_row ];

					$mycred = mycred( $requirement['type'] );

					if ( $level > 0 )
						$requirement['amount'] = $level_value;

					if ( $requirement['by'] == 'count' ){
					
						$rendered_row = sprintf( _x( '%s for "%s" x %d', '"Points" for "reference" x times', 'mycred' ), $mycred->plural(), $requirement['ref'], $requirement['amount'] );
						$rendered_row.=$progressbars[$key];
					}else{
					
						$rendered_row = sprintf( _x( '%s %s for "%s"', '"Gained/Lost" "x points" for "reference"', 'mycred' ), ( ( $requirement['amount'] < 0 ) ? __( 'Lost', 'mycred' ) : __( 'Gained', 'mycred' ) ), $mycred->format_creds( $requirement['amount'] ), $requirement['ref'] );
												$rendered_row.=$progressbars[$key];
					}

					$compare = _x( 'OR', 'Comparison of badge requirements. A OR B', 'mycred' );
					if ( $setup['compare'] === 'AND' )
						$compare = _x( 'AND', 'Comparison of badge requirements. A AND B', 'mycred' );

					if ( $req_count > 1 && $requirement_row+1 < $req_count )
						$rendered_row .= ' <span>' . $compare . '</span>';

					$level_req[] = $rendered_row;

					$key++;
				}

				if ( empty( $level_req ) ) continue;

				$output[] = $level_label . '<ul class="mycred-badge-requirement-list"><li>' . implode( '</li><li>', $level_req ) . '</li></ul>';

			}

			if ( (int) get_post_meta( $badge_id, 'manual_badge', true ) === 1 )
				$output[] = '<strong><small><em>' . __( 'This badge is manually awarded.', 'mycred' ) . '</em></small></strong>';

			$reply = implode( '', $output );

		}

		return apply_filters( 'mycred_badge_display_requirements', $reply, $badge_id );

	}
endif;


/**
 * Badge Progress Reached
 * Checks what level a user has earned for a badge and make progress for its level and requirements. Returns array of progress bars.
 * @since 1.7
 * @version 1.0
 */
if ( ! function_exists( 'mycred_badge_progress_reached' ) ) :
	function mycred_badge_progress_reached( $user_id = NULL, $badge_id = NULL,$level_req= NULL,$settings= NULL) {
		
		$user_id = absint( $user_id );
		if ( $user_id === 0 ) return false;

		$badge_id = absint( $badge_id );
		if ( $badge_id === 0 ) return false;

		global $wpdb;

		//get badge level
		$levels            = mycred_get_badge_levels( $badge_id );

		if ( empty( $levels ) ) return false;

		$base_requirements = $levels[0]['requires'];
		$compare           = $levels[0]['compare'];
		$requirements      = count( $base_requirements );
		$level_reached     = false;
		$results           = array();

		// Based on the base requirements, we first get the users log entry results
		if ( ! empty( $base_requirements ) ) {
			foreach ( $base_requirements as $requirement_id => $requirement ) {

				if ( $requirement['type'] == '' )
					$requirement['type'] = MYCRED_DEFAULT_TYPE_KEY;

				$mycred = mycred( $requirement['type'] );
				if ( $mycred->exclude_user( $user_id ) ) continue;

				$having = 'COUNT(*)';
				if ( $requirement['by'] != 'count' )
					$having = 'SUM(creds)';

				$query = $wpdb->get_var( $wpdb->prepare( "SELECT {$having} FROM {$mycred->log_table} WHERE ctype = %s AND ref = %s AND user_id = %d;", $requirement['type'], $requirement['reference'], $user_id ) );
				if ( $query === NULL ) $query = 0;

				$results[ $requirement['reference'] ] = $query;

			}
		}

		// Next we loop through the levels and see compare the previous results to the requirements to determan our level
		$progresbars= array();
		$key=0;
		$levelprogressbars='';

		foreach ( $levels as $level_id => $level_setup ) {

			//this condition here skip levels if its not same as reqquired level called in params 
			if ($level_id != $level_req)
					continue;
			$reqs_met = 0;	
			$ref_array = array();
			//code for requirement progress
			$total_log_count = 0;
			$total_req_count = 0;
			$log_for_level = 0;

			foreach ( $level_setup['requires'] as $requirement_id => $requirement ) {
				
				if ( $results[ $requirement['reference'] ] >= $requirement['amount'] ){
					$reqs_met++;
				}
				//check to stop making multiple progressbar for same level
				if (!in_array($requirement['reference'], $ref_array) ) {
					//calculating requirement progress 
					
					// $current_progress = ($results[ $requirement['reference'] ] / $requirement['amount'])*100;
					
					
					
					$reference = $requirement['reference'] ?? null;
                    $amount    = isset($requirement['amount']) ? (float)$requirement['amount'] : 0;
                    $current   = isset($results[$reference]) ? (float)$results[$reference] : 0;
                    
                    // Prevent division by zero
                    if ($amount > 0) {
                        $current_progress = ($current / $amount) * 100;
                    } else {
                        $current_progress = 0;
                    }
                    
                    // Clamp between 0 and 100
                    $current_progress = max(0, min(100, $current_progress));
					
					
					
					//if req met and user keep doing action we will keep progress to 100
					if ($current_progress > 100) {
						$current_progress = 100;
					}
					$progressbars[$key] = mycred_get_progressbar_for_badges($current_progress,$settings);
    				$key++;
    			 array_push($ref_array,$requirement['reference']);
				}
				//calculating sum for calculating total level progress
				$log_for_level = $results[ $requirement['reference'] ];
				//this here is checking that if total logs are greater than given level requirement so we add req instead of log 
    			if ($results[ $requirement['reference'] ] > $requirement['amount'] ) {
    			 	$log_for_level = $requirement['amount'];
    			}
				$total_log_count += (int) $results[ $requirement['reference'] ];
				$total_req_count += (int) $requirement['amount'];
			}
  
			//code here for level progress
		if (count($level_setup['requires']) > 1 ){
			# code...
			//check if level is reached with AND condition
			if ( $compare === 'AND' && $reqs_met >= $requirements ){
				$current_progress_level = ($reqs_met / $requirements)*100;
				if ($current_progress_level > 100) {
					$current_progress_level = 100;
				}
				$levelprogressbars = mycred_get_progressbar_for_badges($current_progress_level,$settings);
				$level_reached = $level_id;
			}elseif ( $compare === 'OR' && $reqs_met > 0 ){
				//check if level is reached with OR condition
				$current_progress_level = 100;
    			$levelprogressbars = mycred_get_progressbar_for_badges($current_progress_level,$settings);
				$level_reached = $level_id;
			}else{
				//if level is not  reached then show current progress
				$current_progress_level = ($total_log_count / $total_req_count)*100;
				$current_progress_level=number_format((float)$current_progress_level, 2, '.', '');
				if ($current_progress_level > 100) {
					$current_progress_level = 100;
				}
				$levelprogressbars = mycred_get_progressbar_for_badges($current_progress_level,$settings);
			}
	}
			
		}
		do_action( 'mycred_badge_progress_reached', $user_id, $badge_id, $level_reached );
		$progress =  array();
		array_push($progress,$progressbars);
		array_push($progress,$levelprogressbars);

		return $progress;

	}
endif;

/**
 * Render HTML For Progress Bar for badges
 * @since 1.7
 * @version 1.0
 */
function mycred_get_progressbar_for_badges($current_progress_level,$settings){
	$animation = $settings['animate'] == 1 ? 'active' : '';
	$stripe = $settings['stripe'] == 1 ? 'progress-bar-striped' : '';
	$settings['type'] = $settings['type'] == '' ? 'bars' : $settings['type'];
	$progrssBar = '';
	if($settings['type'] == 'bars' ){
		$progrssBar =  '<div class="progress">
  				<div class="progress-bar progress-bar-success '.$stripe.'  '.$animation.'" role="progressbar" aria-valuenow="'.round((int)$current_progress_level).'"
  				aria-valuemin="0" aria-valuemax="100" style="width:'.round((int)$current_progress_level).'%;background-color:'.$settings['bgcolor'].';">
    			'.round((int)$current_progress_level).'% Complete (success)</div></div>';
	}

	if ($settings['type'] == 'radial') {
		# code...
		$progrssBar = '<div class="c100 p'.round((int)$current_progress_level).' '.$settings['bgcolor'].'">
                    <span>'.round((int)$current_progress_level).'%</span>
                    <div class="slice">
                        <div class="bar"></div>
                        <div class="fill"></div>
                    </div>
                </div>
';		
	}


 	return apply_filters('get_user_badge_progressbar',$progrssBar);
	
}