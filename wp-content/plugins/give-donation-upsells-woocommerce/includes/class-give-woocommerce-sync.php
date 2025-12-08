<?php
/**
 * Class Give_WooCommerce_Sync
 * The Role of this class to keep the Give Donation and WC_Order both sync.
 *
 * @link       https://givewp.com
 * @since      1.0.0
 *
 * @package    Give_WooCommerce_Sync
 * @subpackage Give_WooCommerce_Sync
 */

/**
 * Keep Give donation and wc order sync.
 *
 * @package    Give_WooCommerce_Sync
 * @subpackage Give_WooCommerce_Sync
 * @author     GiveWP <https://givewp.com>
 */
class Give_WooCommerce_Sync {

	/**
	 * Store WC Order object.
	 *
	 * @since 1.0.0
	 * @var \WC_Order $wc_order Store order object.
	 */
	protected $wc_order;

	/**
	 * Store the Give Donation.
	 *
	 * @since 1.0.0
	 * @var array $donation Store the Give Donations.
	 */
	protected $donations;

	/**
	 * Give_WooCommerce_Sync constructor.
	 *
	 * @param \WC_Order|int $wc_order WooCommerce order id.
	 */
	public function __construct( $wc_order ) {
		$this->wc_order = $wc_order;

		if( ! ( $this->wc_order instanceof WC_Order ) ) {
			$this->wc_order = wc_get_order( $wc_order );
			$this->wc_order = $this->wc_order instanceof WC_Order_Refund
				? new WC_Order( $wc_order )
				: $this->wc_order;
		}

		// Get and set the wc order id.
		$this->set_donations();
	}

	/**
	 * Set the WooCommerce order.
	 *
	 * @since 1.0.0
	 */
	public function set_donations() {
		// Bailout.
		if( ! $this->wc_order ) {
			return;
		}

		// Get the Give donations.
		$give_donation_ids = give_wc_donation_by_order_id( $this->wc_order );

		// If wc order has give donations.
		if ( false !== $give_donation_ids ) {
			foreach ( $give_donation_ids as $donation_id ) {
				/** @var \Give_Payment donations */
				$this->donations[] = $donation_id;
			}
		}
	}

	/**
	 * Get the WC order.
	 *
	 * @since 1.0.0
	 * @return \WC_Order
	 */
	public function get_wc_order() {
		return $this->wc_order;
	}

	/**
	 * Check if the WC order is exists or not, if not then remove the Give donation
	 * associated with it.
	 *
	 * @since 1.0.0
	 */
	public function check_and_delete_donation() {
		// If WC order is not exists.
		if ( ! $this->wc_order ) {
			return;
		}

		foreach ( $this->donations as $donation_id ) {
			/**
			 * Before order deletion.
			 *
			 * @since 1.0.0
			 *
			 * @param integer   $donation_id Give Payment Object.
			 * @param \WC_Order $wc_order    WC Order object.
			 */
			do_action( 'give_wc_before_delete_donation', $donation_id, $this->wc_order );

			// Delete Give donation.
			give_delete_donation( $donation_id );
		}
	}

	/**
	 * Update status as per the wc order.
	 *
	 * @since 1.0.0
	 */
	public function sync_status() {
		// Get the WC order status.
		$wc_order_status = $this->wc_order->get_status();

		// Bail out if donations not exist.
		if ( ! isset( $this->donations ) ) {
			return;
		}

		/** @var integer $donation_id */
		foreach ( $this->donations as $donation_id ) {

			// If wc order and donation status is not same.
			if ( get_post_status( $donation_id ) !== $wc_order_status ) {

				// Convert status into Give Donation's available status.
				switch ( $wc_order_status ) {
					case 'pending' :
					case 'processing' :
					case 'on-hold' :
						$wc_order_status = 'pending';
						break;
				}

				/**
				 * Modify the status when wc order status changed.
				 *
				 * @since 1.0.0
				 *
				 * @param string    $wc_order_status WC Order status.
				 * @param integer   $donation_id     Give Donation ID.
				 * @param \WC_Order $wc_order        wc order.
				 */
				$wc_order_status = apply_filters( 'give_wc_sync_payment_status', $wc_order_status, $donation_id, $this->wc_order );

				// Update Give donation status.
				give_update_payment_status( $donation_id, $wc_order_status );

				// Add not to wc order about donation status changed.
				wc_create_order_note( $this->wc_order->get_id(),
					sprintf( __( 'Donation "%1$s" status changed to %2$s', 'give-woocommerce' ),
						give_get_donation_form_title( $donation_id ),
						$wc_order_status
					)
				);

				// Insert note to Give donation.
				give_insert_payment_note( $donation_id,
					sprintf(
						__( 'Reference WC Order #%1$s changed to %2$s', 'give-woocommerce' ),
						$this->wc_order->get_id(),
						$wc_order_status
					)
				);
			}
		}
	}

	/**
	 * Update donation address when WC order's billing address changed.
	 *
	 * @since 1.0.0
	 */
	public function update_donation_address() {
		// Bailout.
		if ( ! $this->wc_order ) {
			return;
		}

		// Get billing details.
		$billing_address = $this->wc_order->get_address( 'billing' );

		// Give donation and wc_order mapping.
		$meta_array = array(
			'_give_donor_billing_address1' => $billing_address['address_1'],
			'_give_donor_billing_address2' => $billing_address['address_2'],
			'_give_donor_billing_city'     => $billing_address['city'],
			'_give_donor_billing_country'  => $billing_address['country'],
			'_give_donor_billing_state'    => $billing_address['state'],
			'_give_donor_billing_zip'      => $billing_address['postcode'],
		);

		foreach ( $meta_array as $meta_key => $meta_value ) {
			if ( empty( $this->donations ) ) {
				continue;
			}
			/** @var integer $donation Give Donation ID. */
			foreach ( $this->donations as $donation_id ) {
				give_update_payment_meta( $donation_id, $meta_key, $meta_value );
			}
		}
	}

	/**
	 * Destruction of this class.
	 *
	 * @since 1.0.0
	 */
	public function __destruct() {
	}
}
