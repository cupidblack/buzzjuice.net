<?php
/**
 * Ensure helper exists. If not, provide a conservative fallback that treats everyone as non-subscriber.
 * This prevents fatal errors if the required file wasn't loaded or names differ.
 */
	// Fallback shim only if the helper file is absent so plugin remains functional in dev environments.
	if ( ! function_exists( 'bzj_allowed_subscription_roles' ) ) {
		function bzj_allowed_subscription_roles() {
			return array(
				'administrator',
                'classic_lifestyle',
				'silver_lifestyle',
				'rockstar_lifestyle',
				'premium_lifestyle',
				'jewel_affiliate',
			);
		}
	}

	if ( ! function_exists( 'bzj_user_has_subscription_role' ) ) {
		function bzj_user_has_subscription_role( $user = null ) {
			if ( is_null( $user ) ) {
				if ( ! is_user_logged_in() ) {
					return false;
				}
				$user = wp_get_current_user();
			} elseif ( is_numeric( $user ) ) {
				$user = get_userdata( (int) $user );
			}

			if ( ! $user || empty( $user->roles ) ) {
				return false;
			}

			return (bool) array_intersect( (array) $user->roles, bzj_allowed_subscription_roles() );
		}
	}
