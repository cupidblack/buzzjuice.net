<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Prevent direct access
}

add_filter( 'automatewoo/triggers', 'myCred_triggers' );

/**
 * This function return an array of pointtype,rank,badges,custom triggers.
 *
 * @param array $triggers
 * @return array
 */
function myCred_triggers( $triggers ) {

	// set a unique name for the trigger and then the class name
	
	// triggers for ranks
	$triggers['earned_rank'] = 'My_AutomateWoo_Earned_Rank_Trigger';
	$triggers['rank_promoted'] = 'My_AutomateWoo_Rank_Promote_Trigger';
	$triggers['rank_demoted'] = 'My_AutomateWoo_Rank_Demote_Trigger';

	// triggers for badges
	$triggers['earned_badge'] = 'My_AutomateWoo_Earned_Badge_Trigger';
	$triggers['earned_badge_lvl'] = 'My_AutomateWoo_Earned_Badge_Lvl_Trigger';


	// triggers for pointypes
	$triggers['balance_changes'] = 'My_AutomateWoo_User_Balance_Change_Trigger';
	$triggers['balance_increase'] = 'My_AutomateWoo_User_Balance_Increase_Trigger';
	$triggers['balance_decrease'] = 'My_AutomateWoo_User_Balance_Decrease_Trigger';
	$triggers['balance_reaches_zero'] = 'My_AutomateWoo_User_Balance_Reaches_Zero_Trigger';
	$triggers['balance_reaches_negative'] = 'My_AutomateWoo_User_Balance_Reaches_Negative_Trigger';


	
	return $triggers;
}
