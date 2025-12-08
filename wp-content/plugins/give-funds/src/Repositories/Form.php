<?php
namespace GiveFunds\Repositories;

/**
 * Class Form
 * @package GiveFunds\Repositories
 *
 * @since 1.0.0
 */
class Form {
	/**
	 * Get fund display type.
	 *
	 * @param  int  $formId
	 *
	 * @return bool|mixed
	 * @since 1.0.0
	 *
	 */
	public function getFundDisplayType( $formId ) {
		return give_get_meta( $formId, 'give_funds_form_choice', true );
	}

	/**
	 * Get fund id associated with form when "Fund Display" set to 'admin_choice'
	 *
	 * @since 1.0.0
	 * @param int $formId
	 *
	 * @return int|null
	 */
	public function getAdminDefinedFundId( $formId ) {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT fund_id
				FROM {$wpdb->give_fund_form_relationship}
				WHERE form_id = %d
				LIMIT 1
				",
				$formId
			)
		);

		return $result ? (int) $result : null;
	}
}
