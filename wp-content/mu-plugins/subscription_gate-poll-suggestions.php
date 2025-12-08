<?php
/*
Plugin Name: Buzzjuice — Poll "Other" Field Gate (MU)
Description: Hides TotalPoll "Other" choice field for non-subscribers and replaces it with a CTA placeholder.
Author: Buzzjuice Dev
Version: 1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Require subscription helpers if present.
 */
if ( file_exists( ABSPATH . 'shared/subscription_gate_helpers.php' ) ) {
	require_once ABSPATH . 'shared/subscription_gate_helpers.php';
}

// Conservative fallback if helpers not available
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

/**
 * Inject CTA and hide/show the "Other" poll choice field
 */
add_action( 'wp_footer', function () {
	// Only output if TotalPoll is present on page
	if ( ! function_exists( 'totalpoll' ) ) {
		return;
	}

	$cta_url  = esc_url( home_url( '/streams/ww-sso-bridge.php?redirect_to=go-pro' ) );
	$cta_text = 'Tap here and subscribe to add discussion topic suggestions';

	// Style borrowed from group share CTA
	?>
	<style>
		/* Always hide the Other field by default */
		form > div.totalpoll-questions > div > div > div.totalpoll-question-choices > label.totalpoll-question-choices-item-type-other {
			display: none !important;
		}

		/* CTA placeholder */
		.bzj-poll-cta-wrap {
			margin: 12px 0 !important;
			font-family: inherit;
			min-width: 100%;
		}
		.bzj-poll-cta-link {
			display: block !important;
			padding: 14px 16px !important;
			border-radius: 8px !important;
			background: #fff7ed !important;
			border: 2px dashed #ff8c00 !important;
			text-align: center !important;
			font-weight: 700 !important;
			color: #111 !important;
			text-decoration: none !important;
		}
		.bzj-poll-cta-link:hover {
			text-decoration: underline !important;
		}
}
	</style>

	<script>
	(function(){
		var userHasRole = <?php echo bzj_user_has_subscription_role() ? 'true' : 'false'; ?>;
		var selector = 'form > div.totalpoll-questions > div > div > div.totalpoll-question-choices > label.totalpoll-question-choices-item-type-other';

		function togglePollOtherField(){
			var field = document.querySelector(selector);

			if (!field) return;

			if (userHasRole) {
				// Show actual Other field
				field.style.display = 'block';
			} else {
				// Replace with CTA
				var ctaWrap = document.createElement('div');
				ctaWrap.className = 'bzj-poll-cta-wrap';
				ctaWrap.innerHTML = '<a href="<?php echo esc_url( $cta_url ); ?>" class="bzj-poll-cta-link"><?php echo esc_html( $cta_text ); ?></a>';
				field.parentNode.insertBefore(ctaWrap, field);
			}
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', togglePollOtherField);
		} else {
			togglePollOtherField();
		}
	})();
	</script>
	<?php
});
