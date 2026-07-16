<?php
/**
 * Store Credit Shortcode
 *
 * @package     AffiliateWP
 * @subpackage  Payouts/Methods/StoreCredit
 * @since       2.29.0
 */

namespace AffiliateWP\Core\Payouts\Methods\StoreCredit;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store Credit Shortcode class
 *
 * @since 2.29.0
 */
class AffiliateWP_Store_Credit_Shortcode {

	/**
	 * Constructor
	 *
	 * @since 2.29.0
	 */
	public function __construct() {
		add_shortcode( 'affiliate_store_credit', [ $this, 'shortcode' ] );
	}

	/**
	 * [affiliate_store_credit] shortcode.
	 *
	 * @since 2.29.0
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string Shortcode output.
	 */
	public function shortcode( $atts, $content = null ) {

		if ( ! ( affwp_is_affiliate() && affwp_is_active_affiliate() ) ) {
			return '';
		}

		ob_start();

		echo affwp_store_credit_balance();

		$content = ob_get_clean();

		return do_shortcode( $content );
	}
}
