<?php 
if ( !class_exists( 'MyCred_AutomateWoo' ) ) :
	class MyCred_AutomateWoo {

		private static $_instance;

		public static function get_instance() {
			if ( null == self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		public function __construct() {
			add_action('woocommerce_init' ,array( $this, 'includes_file') );
			add_filter( 'automatewoo/triggers', array( $this, 'myCred_triggers' ) );
			
		}
		public function includes_file(){
			$this->file_require();
		}


	/**
	 * This function return an array of pointtype,rank,badges,custom triggers.
	 *
	 * @param array $triggers
	 * @return array
	 */
	public function myCred_triggers( $triggers ) {

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


	public function file_require() {
		if ( !class_exists( 'AutomateWoo_Loader') || !class_exists( 'WooCommerce') ) {
			return;
		}
			// triggers
			//require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/functions.php';

			//badges triggers
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/badges/earned-badge-lvl.php';
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/badges/earned-badge.php';


			// ranks triggers
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/ranks/earned-rank.php';
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/ranks/rank-promoted.php';
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/ranks/rank-demoted.php';

			// point-types triggers
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/pointtypes/balance-changes.php';
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/pointtypes/balance-increase.php';
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/pointtypes/balance-decrease.php';
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/pointtypes/balance-reaches-zero.php';
		require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/triggers/pointtypes/balance-reaches-negative.php';

	}
}

MyCred_AutomateWoo::get_instance();

endif;
