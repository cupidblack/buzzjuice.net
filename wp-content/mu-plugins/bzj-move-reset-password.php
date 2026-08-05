<?php
/*
Plugin Name: Buzz Move Reset Password Elements
Plugin URI:  https://example.com/
Description: Move the "Generate Password" button and the reset key into the New Password block on the wp-login.php reset password form. Single-file plugin, update-proof.
Version:     1.0
Author:      Buzz Juice / (your name)
Text Domain: buzz-move-reset-password
License:     GPLv2 or later
*/

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue inline CSS & JS on the reset password login page.
 *
 * Runs only on action=resetpass or action=rp.
 */
function buzz_move_reset_password_enqueue() {
	$action = isset( $_REQUEST['action'] ) && is_string( $_REQUEST['action'] ) ? sanitize_text_field( $_REQUEST['action'] ) : '';

	if ( 'resetpass' !== $action && 'rp' !== $action ) {
		return; // only run on reset password pages
	}

	// Register a no-src script handle (so we can add inline JS and localize).
	wp_register_script( 'buzz-reset-pass', '' , array(), false, true );
	wp_enqueue_script( 'buzz-reset-pass' );

	// Localized strings for JS.
	$strings = array(
		'label' => __( 'Generate New Password', 'buzz-move-reset-password' ),
	);
	wp_localize_script( 'buzz-reset-pass', 'BuzzResetPassword', $strings );

	// Inline CSS to right-align the moved button using Flexbox and give it a sensible min-width.
	$css = '
/* Buzz: move Reset Password button styling inside the New Password block */
.user-pass1-wrap .reset-pass-submit{
	display: flex;
	justify-content: flex-end;
	margin-top: 0.75rem;
}
.user-pass1-wrap .reset-pass-submit .wp-generate-pw{
	min-width: 12rem;
}
button.button.wp-generate-pw.hide-if-no-js.skip-aria-expanded {
    margin: 5px 0 10px;
    width: auto;
    padding: 0 10px;
    font-size: 10px;
    min-width: 5rem;
    min-height: 1rem;
}
.user-pass1-wrap .moved-reset-btn { margin: 0; }
';
	// Attach inline style to the login handle (already registered/enqueued on login page).
	wp_add_inline_style( 'login', $css );

	// Inline JS: move the button and input, preserve event listeners (move nodes), use MutationObserver fallback.
	$js = <<<'JS'
(function() {
	"use strict";

	function attemptMove() {
		var form = document.querySelector('form#resetpassform, form[name="resetpassform"]');
		if ( ! form ) {
			return false;
		}

		// Idempotency: if we've already enhanced the form, bail out.
		if ( form.dataset.buzzResetEnhanced === '1' ) {
			return true;
		}

		// Locate the important nodes inside the form.
		var wpPwd = form.querySelector('.wp-pwd');
		var pwWeak = form.querySelector('.pw-weak');

		if ( ! wpPwd || ! pwWeak ) {
			// If those containers aren't present yet, wait for DOM mutations.
			return false;
		}

		var srcBtn = form.querySelector('.wp-generate-pw'); // the original Generate button
		var srcRp = form.querySelector('input[name="rp_key"]'); // hidden reset key
		var originalWrapper = form.querySelector('p.submit.reset-pass-submit'); // original bottom wrapper (may contain both generate + save)

		// Move the generate button (preserves event listeners).
		if ( srcBtn ) {
			// Update visible text & aria-label to localized label if available.
			if ( typeof BuzzResetPassword !== 'undefined' && BuzzResetPassword.label ) {
				try { srcBtn.textContent = BuzzResetPassword.label; } catch (e) {}
				try { srcBtn.setAttribute('aria-label', BuzzResetPassword.label); } catch (e) {}
				try { srcBtn.title = BuzzResetPassword.label; } catch (e) {}
			}

			// Create a small wrapper to match the original structure and insert before pwWeak.
			var p = document.createElement('p');
			p.className = 'submit reset-pass-submit moved-reset-btn';
			p.appendChild( srcBtn ); // moves the node
			pwWeak.parentNode.insertBefore( p, pwWeak );
		}

		// Move the rp_key input (no duplication). Insert it before pwWeak as well.
		if ( srcRp ) {
			try {
				pwWeak.parentNode.insertBefore( srcRp, pwWeak );
			} catch (e) {
				// ignore
			}
		}

		// Cleanup: If original wrapper exists and is now empty of interactive elements, remove it.
		if ( originalWrapper && originalWrapper !== form.querySelector('.user-pass1-wrap .moved-reset-btn') ) {
			// Check for inputs/buttons inside original wrapper.
			var interactive = originalWrapper.querySelector('input, button, select, textarea, a');
			if ( ! interactive ) {
				try { originalWrapper.parentNode.removeChild(originalWrapper); } catch (e) {}
			}
		}

		// Mark as processed and return success.
		form.dataset.buzzResetEnhanced = '1';
		return true;
	}

	function init() {
		// Try immediately.
		if ( attemptMove() ) {
			return;
		}

		// Fallback: observe DOM mutations for dynamic DOM insertions (plugins/themes may modify after load).
		var observer = new MutationObserver(function(mutations, obs) {
			if ( attemptMove() ) {
				try { obs.disconnect(); } catch (e) {}
			}
		});

		observer.observe(document.body, { childList: true, subtree: true });

		// Safety timeout: try again in case of slow JS.
		setTimeout(attemptMove, 3000);
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
JS;

	wp_add_inline_script( 'buzz-reset-pass', $js );
}
add_action( 'login_enqueue_scripts', 'buzz_move_reset_password_enqueue' );











// Buzz — MU plugin to customize wp-login.php checkemail messages.
// Replaces the default messages for the 'confirm' and 'registered' wp-login checkemail notices
// with the requested wording, uses semantic <p> markup, and enforces a 20px font-size for readability.

/**
 * Return true if this request is the login page.
 *
 * Uses $pagenow when available, then SCRIPT_NAME / PHP_SELF fallbacks.
 *
 * @return bool
 */
function buzz_is_login_screen() {
	global $pagenow;

	if ( isset( $pagenow ) && 'wp-login.php' === $pagenow ) {
		return true;
	}

	$script = '';
	if ( isset( $_SERVER['SCRIPT_NAME'] ) ) {
		$script = wp_basename( wp_unslash( $_SERVER['SCRIPT_NAME'] ) );
	} elseif ( isset( $_SERVER['PHP_SELF'] ) ) {
		$script = wp_basename( wp_unslash( $_SERVER['PHP_SELF'] ) );
	}

	return ( 'wp-login.php' === $script );
}

/**
 * Build replacement messages keyed by error code.
 *
 * The strings contain HTML (<p>, <a>, <strong>) intentionally and are not escaped here.
 *
 * @return array<string,string>
 */
function buzz_checkemail_replacement_messages() {
	$login_url = esc_url( wp_login_url() );

	$confirm = sprintf(
		/* translators: %s: Link to the login page. */
		__( '<p>Check your inbox/junk/spam for our email then tap on the confirmation link.</p><p>If the email is not in your inbox, please check your junk/spam folder for the email then mark as <strong>Not Spam</strong>.</p><p><a href="%s">Go to login>></a></p>', 'buzz-mu-checkemail' ),
		$login_url
	);

	$registered = sprintf(
		/* translators: %s: Link to the login page. */
		__( '<p>Registration complete. Please check your inbox/junk/spam for our email then tap on the activation link or <a href="%s">go to the login page</a>.</p><p>If the email is not in your inbox, please check your junk/spam folder for it then mark as <strong>Not Spam</strong>.</p>', 'buzz-mu-checkemail' ),
		$login_url
	);

	return array(
		'confirm'    => $confirm,
		'registered' => $registered,
	);
}

/**
 * Primary replacement: operate on the WP_Error object passed to wp_login_errors.
 *
 * Preserves other messages and their data.
 *
 * @param WP_Error $errors      WP_Error object.
 * @param string   $redirect_to Redirect destination (unused).
 * @return WP_Error
 */
function buzz_mu_replace_wp_login_errors( $errors, $redirect_to ) {
	// Only run on the login screen.
	if ( ! buzz_is_login_screen() ) {
		return $errors;
	}

	if ( ! ( $errors instanceof WP_Error ) ) {
		return $errors;
	}

	$replacements = buzz_checkemail_replacement_messages();

	// If the errors object does not include either target code, nothing to do.
	$codes = $errors->get_error_codes();
	if ( ! $codes ) {
		return $errors;
	}

	$needs_replace = false;
	foreach ( array_keys( $replacements ) as $key ) {
		if ( in_array( $key, $codes, true ) ) {
			$needs_replace = true;
			break;
		}
	}
	if ( ! $needs_replace ) {
		return $errors;
	}

	// Build a new WP_Error preserving other messages/data, but replacing target codes with our HTML.
	$new_errors = new WP_Error();

	foreach ( $errors->get_error_codes() as $code ) {
		$data = $errors->get_error_data( $code );

		// If we have a replacement for this code, add the replacement once using same data.
		if ( isset( $replacements[ $code ] ) ) {
			$new_errors->add( $code, $replacements[ $code ], $data );
			continue;
		}

		// Otherwise re-add every original message for that code.
		foreach ( $errors->get_error_messages( $code ) as $message ) {
			$new_errors->add( $code, $message, $data );
		}
	}

	return $new_errors;
}
add_filter( 'wp_login_errors', 'buzz_mu_replace_wp_login_errors', 10, 2 );

/**
 * Fallback: if the checkemail parameter is present and the above filter did not run,
 * replace the login_message output. Runs late to avoid overriding other plugins.
 *
 * @param string $message Current login message HTML.
 * @return string
 */
function buzz_mu_login_message_fallback( $message ) {
	if ( ! buzz_is_login_screen() ) {
		return $message;
	}

	if ( empty( $_GET['checkemail'] ) ) {
		return $message;
	}

	$key = sanitize_key( wp_unslash( $_GET['checkemail'] ) );
	$replacements = buzz_checkemail_replacement_messages();

	if ( ! isset( $replacements[ $key ] ) ) {
		return $message;
	}

	// If the existing message already includes our replacement text, return original to avoid duplication.
	if ( strpos( $message, 'Check your inbox/junk/spam' ) !== false || strpos( $message, 'Registration complete. Please check your inbox' ) !== false ) {
		return $message;
	}

	// Wrap in login notice container for consistent styles.
	$return  = '<div id="login-message" class="message notice notice-success">';
	$return .= $replacements[ $key ];
	$return .= '</div>';

	return $return;
}
// add_filter( 'login_message', 'buzz_mu_login_message_fallback', 99 );

/**
 * Add focused CSS for login messages (20px font-size + improved readability).
 */
function buzz_mu_login_message_css() {
	if ( ! buzz_is_login_screen() ) {
		return;
	}
	?>
	<style>
		/* Make login notices larger and easier to read */
		.login .message,
		#login-message,
		.login .message p,
		#login-message p {
			font-size: 20px !important;
			line-height: 1.6 !important;
			max-width: 48em;
			margin: 0 0 1rem 0;
		}
		/* Ensure link is clearly visible inside notice */
		#login-message a,
		.login .message a {
			text-decoration: underline;
		}
	</style>
	<?php
}
add_action( 'login_enqueue_scripts', 'buzz_mu_login_message_css' );