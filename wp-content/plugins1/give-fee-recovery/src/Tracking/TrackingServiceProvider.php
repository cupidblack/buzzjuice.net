<?php
namespace GiveFeeRecovery\Tracking;

use Give\ServiceProviders\ServiceProvider;
use GiveFeeRecovery\Helpers\Form\Form;

/**
 * Class TrackingServiceProvider
 *
 * @package GiveFeeRecovery\Tracking
 * @since 1.9.0
 */
class TrackingServiceProvider implements ServiceProvider {
	/**
	 * @inheritdoc
	 */
	public function register() {
	}

	/**
	 * @inheritdoc
	 */
	public function boot() {
		add_filter(
			'give_telemetry_form_uses_addon_fee_recovery',
			function ( $result, $formId ) {
				return Form::canRecoverFee( $formId );
			},
			10,
			2
		);
	}
}
