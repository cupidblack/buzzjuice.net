<?php
/*
Plugin Name: Buzzjuice — Poll "Other" Suggestion Gate (MU)
Description: Role-based gating for TotalPoll custom suggestion field, with native UI and CTA replacement.
Author: Buzzjuice Dev
Version: 2.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Shared subscription helpers
if ( file_exists( ABSPATH . 'shared/subscription_gate_helpers.php' ) ) {
	require_once ABSPATH . 'shared/subscription_gate_helpers.php';
}

// Fallback: allowed roles
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
 * Let TotalPoll render questions naturally (do NOT override allowCustomChoice server-side).
 * For users not allowed, replace native field with CTA.
 */
add_action( 'wp_footer', function () {
	if ( is_admin() ) return;

	// If user has allowed role, do nothing.
	if ( bzj_user_has_subscription_role() ) return;

	?>
	<script>
	document.addEventListener("DOMContentLoaded", function() {
		// Selector for TotalPoll custom ("Other") fields.
		var selectors = [
			'.totalpoll-choice--custom', // Modern Basic template
			'.totalpoll-question-custom-choice', // Legacy template
			'label.totalpoll-question-choices-item-type-other' // Your prior selector
		];

		function replaceOtherField(context) {
			selectors.forEach(function(sel) {
				var fields = context.querySelectorAll(sel);
				if (!fields.length) return;
				fields.forEach(function(field) {
					// Prevent multiple replacement
					if (field.classList.contains('bzj-cta-replaced')) return;

					// Clone class for styling; keep layout
					var cta = document.createElement('div');
					cta.className = field.className + ' bzj-cta-replaced';
					cta.style.margin = field.style.margin || '';
					cta.innerHTML = `
						<div class="totalpoll-choice-content" style="padding:14px 16px;border:2px dashed #ff8c00;border-radius:8px;background:#fff7ed;text-align:center;font-family:inherit;">
							<strong>Want to add your own suggestion?</strong><br>
							Active subscribers can add sugestions<br>
							<a href="/subscribe"
								class="bzj-poll-cta-link"
								style="display:inline-block;margin-top:10px;padding:10px 22px;background:#ff8c00;color:#111;border-radius:6px;text-decoration:none;font-weight:700;border:2px dashed #ff8c00;">
								Tap here to subscribe and suggest!
							</a>
						</div>
					`;
					field.parentNode.replaceChild(cta, field);
				});
			});
		}
		// On initial page load
		replaceOtherField(document);

		// On AJAX poll render (TotalPoll triggers `totalpoll.render`)
		document.addEventListener('totalpoll.render', function(e) {
			replaceOtherField(document);
		});
	});
	</script>
	<?php
});