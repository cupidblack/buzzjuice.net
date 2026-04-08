<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Event Espresso
 * Allow users to pay for event tickets using myCRED Points
 * @since 1.4.6
 * @version 1.0.0
 */
if ( ! class_exists( 'EEG_myCRED_Onsite' ) ) :
	class EEG_myCRED_Onsite extends EE_Onsite_Gateway {

		protected $_point_type;
		protected $_purchase_log;
		protected $_exchange_rate;

		/**
		* @return EEG_myCRED_Onsite
		*/
		public function __construct() {

			$this->_supports_sending_refunds = false;

			parent::__construct();
		}

		/**
		 * @param EE_Line_Item $line_item
		 * @return boolean
		 */
		public function do_direct_payment( $payment, $billing_info = null ) {

			$this->log( $billing_info, $payment );
			if ( is_user_logged_in() ) {

				$mycred             = mycred( $this->_point_type );

				$transaction        = $payment->transaction();
				$primary_registrant = $transaction->primary_registration();
				$event_id           = $primary_registrant->event_ID();
			
				$buyer_id           = get_current_user_id();
				$balance            = $mycred->get_users_balance( $buyer_id, $this->_point_type );
		
				$cart_total         = $payment->amount();
				$cost               = $mycred->number( $cart_total * $this->_exchange_rate );
		
				// Solvent
				if ( $balance >= $cost ) {

					// Charge
					$charge = $mycred->add_creds(
						'event_ticket_payment',
						$buyer_id,
						0 - $cost,
						$this->_purchase_log,
						$event_id,
						array( 'ref_type' => 'post' ),
						$this->_point_type
					);

					// Charge was successfull
					if ( $charge ) {

						$payment->set_status( $this->_pay_model->approved_status() );
						$payment->set_txn_id_chq_nmbr( 'MYCRED' . time() . $buyer_id . $event_id );
						// Translators: %1$s is the formatted cost, %2$s is the currency or plural string.
						$payment->set_gateway_response( sprintf( __( 'Account successfully charged %1$s %2$s', 'mycred-toolkit' ), $mycred->format_creds( $cost ), $mycred->plural() ) );

					}

					// Declined for some reason
					else {

						$payment->set_status( $this->_pay_model->declined_status() );
						$payment->set_gateway_response( __( 'Insufficient Funds', 'mycred-toolkit' ) );

					}

				}
			
				// Insolvent
				else {

					$payment->set_status( $this->_pay_model->declined_status() );
					$payment->set_gateway_response( __( 'Insufficient Funds', 'mycred-toolkit' ) );

				}

			}

			return $payment;
		}

		public function do_direct_refund(?EE_Payment $payment, ?array $refund_info = null): ?EE_Payment {

			$payment->set_status( $this->_pay_model->approved_status() );
			$payment->set_gateway_response( __( 'Refund Completed', 'mycred-toolkit' ) );

			return $payment;
		}
	}
endif;
