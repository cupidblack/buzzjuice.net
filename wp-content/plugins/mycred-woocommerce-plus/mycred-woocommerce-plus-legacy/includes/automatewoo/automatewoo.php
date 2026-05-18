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
			$this->file_require();
		}

		public function file_require() {
			if ( !class_exists( 'AutomateWoo_Loader') || !class_exists( 'WooCommerce') ) {
return;
			}
			// triggers
			require_once MYCRED_WOOPLUS_INCLUDES_DIR . 'automatewoo/functions.php';
		
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
