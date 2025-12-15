<?php

if ( ! function_exists( 'yith_wcarc_get_rule_data_to_display' ) ) {
	/**
	 * Get display values for a rule.
	 *
	 * @param $rule
	 *
	 * @return array
	 */
	function yith_wcarc_get_rule_data_to_display( $rule ) {
		$date_format = wc_date_format();

		$roles = [];
		$from  = '';
		$to    = '';

		if ( 'add' === $rule['rule_type'] && ! empty( $rule['role_selected'] ) ) {
			foreach ( $rule['role_selected'] as $single_role ) {
				$roles[] = wp_roles()->roles[ $single_role ]['name'];
			}
		} elseif ( 'replace' === $rule['rule_type'] && ! empty( $rule['replace_roles'] ) ) {
			$roles[] = wp_roles()->roles[ $rule['replace_roles'][1] ]['name'];
		}

		if ( isset( $rule['activation_date'] ) && $rule['activation_date'] ) {
			$from = date_i18n( $date_format, $rule['activation_date'] );
		} elseif ( isset( $rule['date_from'] ) && $rule['date_from'] ) {
			$from = date_i18n( $date_format, strtotime( $rule['date_from'] ) );
		}

		if ( isset( $rule['expiration_date'] ) && $rule['expiration_date'] ) {
			$to = date_i18n( $date_format, $rule['expiration_date'] );
		} elseif ( isset( $rule['date_to'] ) && $rule['date_to'] ) {
			$to = date_i18n( $date_format, strtotime( $rule['date_to'] ) );
		}

		return compact( 'roles', 'from', 'to' );
	}
}