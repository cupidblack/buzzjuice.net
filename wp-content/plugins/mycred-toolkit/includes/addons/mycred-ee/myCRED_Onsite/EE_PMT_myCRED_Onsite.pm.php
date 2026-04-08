<?php
if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 *
 * EE_PMT_myCRED_Onsite
 *
 *
 * @package         Event Espresso
 * @subpackage
 * @author          Gabriel S Merovingi
 *
 */
class EE_PMT_myCRED_Onsite extends EE_PMT_Base {

	/**
	 * Construct
	 * @param EE_Payment_Method $pm_instance
	 * @return EE_PMT_myCRED_Onsite
	 */
	public function __construct( $pm_instance = null ) {

		require_once $this->file_folder() . 'EEG_myCRED_Onsite.gateway.php' ;

		$this->_gateway          = new EEG_myCRED_Onsite();
		$this->_pretty_name      = mycred_label( true );

		parent::__construct( $pm_instance );

		$this->_has_billing_form = false;
	}

	/**
	 * @param \EE_Transaction $transaction
	 * @return \EE_Billing_Attendee_Info_Form
	 */
	public function generate_new_billing_form( EE_Transaction $transaction = null ) {

		return null;
	}

	/**
	 * Gets the form for all the settings related to this payment method type
	 * @return EE_Payment_Method_Form
	 */
	public function generate_new_settings_form() {

		$types = mycred_get_types();

		$_types = array();
		foreach ( $types as $type_id => $label ) {

			$mycred = mycred( $type_id );
			$_types[ $type_id ] = $mycred->plural();

		}

		EE_Registry::instance()->load_helper('Template');
		$form = new EE_Payment_Method_Form(array(
			'extra_meta_inputs' => array(
				'point_type'         => new EE_Select_Input( $_types, array(
					'required'           => true,
					'default'            => MYCRED_DEFAULT_TYPE_KEY,
					'html_help_text'     => __( 'The point type to accept as form of payment.', 'mycred-toolkit' )
				) ),
				'purchase_log'        => new EE_Text_Input( array(
					'html_label_text'     => __( 'Log Template', 'mycred-toolkit' ),
					'html_help_text'      => __( 'The log entry template for successful payments.', 'mycred-toolkit' ),
					'required'            => true,
					'default'             => __( 'Event payment', 'mycred-toolkit' )
				) ),
				'exchange_rate'       => new EE_Text_Input( array(
					'html_label_text'     => __( 'Exchange Rate', 'mycred-toolkit' ),
					'html_help_text'      => __( 'Exchange rate between the store currency and points.', 'mycred-toolkit' ),
					'required'            => true,
					'default'             => 1
				) ),
			)
		));

		return $form;
	}
}
