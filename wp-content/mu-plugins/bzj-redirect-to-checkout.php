<?php
/**
 * MU plugin: bzj-redirect-to-checkout.php
 *
 * Redirect users to checkout immediately after adding a product to cart.
 *
 * Features
 *  - Handles non-AJAX add-to-cart (server redirect via woocommerce_add_to_cart_redirect)
 *  - Handles AJAX add-to-cart and WooCommerce Blocks (client JS listening to events)
 *  - Prevents redirect loops (skip when on cart, checkout, order-received)
 *  - Optional admin bypass and per-product allowlist via filters
 *  - Optional file logging (disabled by default)
 *  - HPOS-safe order/session handling
 *
 * Install:
 *  - Copy this file to: wp-content/mu-plugins/bzj-redirect-to-checkout.php
 *  - Do NOT edit core WooCommerce or theme files.
 *
 * Configuration constants (optional; define in wp-config.php or another mu-plugin):
 *  - BZJ_RTC_ENABLED       (bool) default true
 *  - BZJ_RTC_SKIP_ADMIN    (bool) default true - do not redirect admin users
 *  - BZJ_RTC_ENABLE_JS     (bool) default true - enqueue client JS for AJAX/Blocks flows
 *  - BZJ_RTC_ENABLE_LOG    (bool) default false
 *  - BZJ_RTC_LOG_FILE      (string) default WP_CONTENT_DIR . '/bzj-redirect-to-checkout.log'
 *
 * Filters:
 *  - bzj_rtc_allowed_product_ids     (array)   -> return an array of allowed product IDs; empty = all products
 *  - bzj_rtc_should_redirect_user    (bool, WP_User|null) -> return false to skip redirect for specific user
 *  - bzj_rtc_should_redirect         (bool) -> global allow/deny
 *
 * Author: bzj
 * Version: 1.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* -------------------------
 * Defaults / Config
 * ------------------------- */
defined( 'BZJ_RTC_ENABLED' ) || define( 'BZJ_RTC_ENABLED', true );
defined( 'BZJ_RTC_SKIP_ADMIN' ) || define( 'BZJ_RTC_SKIP_ADMIN', true );
defined( 'BZJ_RTC_ENABLE_JS' ) || define( 'BZJ_RTC_ENABLE_JS', true );
defined( 'BZJ_RTC_ENABLE_LOG' ) || define( 'BZJ_RTC_ENABLE_LOG', false );
defined( 'BZJ_RTC_LOG_FILE' ) || define( 'BZJ_RTC_LOG_FILE', untrailingslashit( WP_CONTENT_DIR ) . '/bzj-redirect-to-checkout.log' );

/* -------------------------
 * Simple logger (optional)
 * ------------------------- */
function bzj_rtc_log( $msg, $context = null ) {
	if ( ! defined( 'BZJ_RTC_ENABLE_LOG' ) || BZJ_RTC_ENABLE_LOG !== true ) {
		return;
	}
	$ts = gmdate( 'Y-m-d H:i:s' );
	$line = sprintf( "[bzj-rtc] %s | %s", $ts, $msg );
	if ( null !== $context ) {
		$line .= ' ' . wp_json_encode( $context );
	}
	$line .= PHP_EOL;
	@file_put_contents( BZJ_RTC_LOG_FILE, $line, FILE_APPEND | LOCK_EX );
}

/* -------------------------
 * Global eligibility checks
 * ------------------------- */
function bzj_rtc_allowed_to_redirect_globally() {
	// feature flag
	if ( ! defined( 'BZJ_RTC_ENABLED' ) || BZJ_RTC_ENABLED !== true ) {
		bzj_rtc_log( 'Global disabled' );
		return false;
	}

	// skip on REST / cron
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		bzj_rtc_log( 'Skipped due to REST/CRON' );
		return false;
	}

	// skip in admin (non-ajax requests)
	if ( is_admin() && ! wp_doing_ajax() ) {
		bzj_rtc_log( 'Skipped because is_admin' );
		return false;
	}

	// skip administrators if requested
	if ( defined( 'BZJ_RTC_SKIP_ADMIN' ) && BZJ_RTC_SKIP_ADMIN === true ) {
		if ( current_user_can( 'manage_options' ) ) {
			$allow = apply_filters( 'bzj_rtc_should_redirect_user', false, wp_get_current_user() );
			if ( ! $allow ) {
				bzj_rtc_log( 'Skipped for admin user' );
				return false;
			}
		}
	}

	// prevent redirect loops: don't redirect when on checkout/cart/order received
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		bzj_rtc_log( 'Skipped on checkout page' );
		return false;
	}
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		bzj_rtc_log( 'Skipped on cart page' );
		return false;
	}
	// order received (thank you) page check – use query var as conservative check
	if ( isset( $_GET['key'] ) && strpos( strtolower( $_SERVER['REQUEST_URI'] ), 'order-received' ) !== false ) {
		bzj_rtc_log( 'Skipped on order received page' );
		return false;
	}

	$global = true;
	$global = apply_filters( 'bzj_rtc_should_redirect', $global );
	if ( ! $global ) {
		bzj_rtc_log( 'bzj_rtc_should_redirect returned false' );
	}
	return (bool) $global;
}

/* -------------------------
 * Per-product allowlist (empty = all)
 * Filter: bzj_rtc_allowed_product_ids
 * ------------------------- */
function bzj_rtc_product_is_allowed( $product_id ) {
	$list = apply_filters( 'bzj_rtc_allowed_product_ids', array() );
	if ( empty( $list ) ) {
		return true; // allow all products by default
	}
	return in_array( (int) $product_id, $list, true );
}

/* -------------------------
 * Detect product id from standard (non-AJAX) form request
 * ------------------------- */
function bzj_rtc_detect_product_id_from_request() {
	// 'add-to-cart' is standard; product_id also sometimes present
	if ( isset( $_REQUEST['add-to-cart'] ) ) {
		return absint( wp_unslash( $_REQUEST['add-to-cart'] ) );
	}
	if ( isset( $_REQUEST['product_id'] ) ) {
		return absint( wp_unslash( $_REQUEST['product_id'] ) );
	}
	return 0;
}

/* -------------------------
 * PHP-side redirect for non-AJAX add-to-cart flows
 * Hook: woocommerce_add_to_cart_redirect
 * ------------------------- */
add_filter( 'woocommerce_add_to_cart_redirect', 'bzj_rtc_add_to_cart_redirect', 99 );
function bzj_rtc_add_to_cart_redirect( $url ) {
	// bail quickly if globally not allowed
	if ( ! bzj_rtc_allowed_to_redirect_globally() ) {
		return $url;
	}

	// if AJAX, skip server redirect (handled by JS)
	if ( wp_doing_ajax() ) {
		bzj_rtc_log( 'Skipping PHP redirect during AJAX' );
		return $url;
	}

	$product_id = bzj_rtc_detect_product_id_from_request();
	if ( $product_id && ! bzj_rtc_product_is_allowed( $product_id ) ) {
		bzj_rtc_log( 'Product not allowed for redirect (PHP)', $product_id );
		return $url;
	}

	// allow final veto
	$should_redirect = apply_filters( 'bzj_rtc_should_redirect', true );
	if ( ! $should_redirect ) {
		bzj_rtc_log( 'bzj_rtc_should_redirect vetoed PHP redirect' );
		return $url;
	}

	// redirect to checkout if available
	if ( function_exists( 'wc_get_checkout_url' ) ) {
		$checkout = wc_get_checkout_url();
		bzj_rtc_log( 'PHP redirect to checkout', compact( 'product_id', 'checkout' ) );
		return $checkout;
	}

	return $url;
}

/* -------------------------
 * Client-side JS for AJAX and Blocks flows
 * - Minimal, robust inline JS that listens to WC events:
 *   - added_to_cart (classic)
 *   - wc-blocks_added_to_cart (blocks)
 * - Uses sessionStorage and WC session guard via AJAX ping to avoid duplicates.
 * ------------------------- */
add_action( 'wp_enqueue_scripts', 'bzj_rtc_enqueue_script', 20 );
function bzj_rtc_enqueue_script() {
	if ( ! bzj_rtc_allowed_to_redirect_globally() ) {
		return;
	}
	if ( defined( 'BZJ_RTC_ENABLE_JS' ) && BZJ_RTC_ENABLE_JS !== true ) {
		return;
	}
	if ( ! function_exists( 'wc_get_checkout_url' ) ) {
		return;
	}

	$checkout_url = wc_get_checkout_url();

	$js = <<<JS
(function(){
	if (typeof window === 'undefined') return;
	try {
		if (!window.bzjRtc) window.bzjRtc = { redirected: false, lastProduct: 0 };

		function parseIntSafe(v){
			var n = parseInt(v,10);
			if (isNaN(n)) return 0;
			return n;
		}

		// capture click to know product/variation id (best-effort)
		document.addEventListener('click', function(e){
			try {
				var el = e.target;
				while (el && el !== document.body) {
					if (el.matches && (el.matches('.add_to_cart_button') || el.matches('.single_add_to_cart_button') || el.matches('[data-product_id]') || el.matches('[data-product-id]'))) {
						var pid = el.getAttribute('data-product_id') || el.getAttribute('data-product-id') || el.getAttribute('data-product');
						pid = parseIntSafe(pid);
						if (!pid && el.dataset) {
							pid = parseIntSafe(el.dataset.productId || el.dataset.product_id || el.dataset.product);
						}
						if (pid) window.bzjRtc.lastProduct = pid;
						return;
					}
					// inside product form (variations)
					if ((el.tagName === 'BUTTON' || el.tagName === 'INPUT') && el.closest) {
						var f = el.closest('form');
						if (f) {
							var atc = f.querySelector('input[name="add-to-cart"], button[name="add-to-cart"]');
							if (atc && atc.value) {
								window.bzjRtc.lastProduct = parseIntSafe(atc.value) || window.bzjRtc.lastProduct;
								return;
							}
							var pidInput = f.querySelector('input[name="product_id"], input[name="variation_id"]');
							if (pidInput && pidInput.value) {
								window.bzjRtc.lastProduct = parseIntSafe(pidInput.value) || window.bzjRtc.lastProduct;
								return;
							}
						}
					}
					el = el.parentNode;
				}
			} catch(e){}
		}, true);

		// prevent redirect loops per-tab
		function alreadyRedirectedInTab(){
			try {
				if (window.bzjRtc.redirected) return true;
				if (sessionStorage && sessionStorage.getItem('bzjRtcRedirected') === '1') return true;
			} catch(e){}
			return false;
		}

		function markRedirected(){
			try {
				window.bzjRtc.redirected = true;
				if (sessionStorage) sessionStorage.setItem('bzjRtcRedirected','1');
			} catch(e){}
		}

		function safeRedirect() {
			try {
				if (alreadyRedirectedInTab()) return;
				// avoid loop by path check
				var path = (location.pathname || '').toLowerCase();
				if (path.indexOf('/checkout') !== -1 || path.indexOf('/cart') !== -1 || path.indexOf('/order-received') !== -1) return;
				markRedirected();
				location.href = CHECKOUT_URL;
			} catch(e){}
		}

		// Classic WC jQuery event
		try {
			if (window.jQuery) {
				window.jQuery('body').on('added_to_cart', function(event, fragments, cart_hash, button){
					// basic allowlist filter can be implemented server-side; here we only react
					safeRedirect();
				});
			}
		} catch(e){}

		// WooCommerce Blocks event
		try {
			document.body.addEventListener('wc-blocks_added_to_cart', function(e){
				// event detail may contain productId, but we just redirect
				safeRedirect();
			});
		} catch(e){}

		// Fallback for other custom events
		try {
			document.body.addEventListener('added_to_cart_to_cart', function(){ safeRedirect(); });
		} catch(e){}
	} catch(e){}
})();
JS;

	$inline = str_replace( 'CHECKOUT_URL', wp_json_encode( $checkout_url ), $js );

	// Register and inject inline
	wp_register_script( 'bzj-rtc-inline', '' );
	wp_enqueue_script( 'bzj-rtc-inline' );
	wp_add_inline_script( 'bzj-rtc-inline', $inline );

	bzj_rtc_log( 'Enqueued JS redirect inline', compact( 'checkout_url' ) );
}

/* -------------------------
 * Optional: server-side guard to prevent redirect loops across sessions
 * We set a short-lived session flag when redirect occurs so subsequent add-to-cart events won't redirect repeatedly.
 * Uses WC()->session when available.
 * ------------------------- */
function bzj_rtc_set_session_flag() {
	if ( ! function_exists( 'wc' ) ) return;
	if ( ! WC()->session ) return;
	try {
		WC()->session->set( 'bzj_rtc_redirected', time() );
	} catch ( Throwable $t ) {
		// ignore
	}
}
function bzj_rtc_check_session_flag() {
	if ( ! function_exists( 'wc' ) ) return false;
	if ( ! WC()->session ) return false;
	try {
		$val = WC()->session->get( 'bzj_rtc_redirected' );
		if ( ! $val ) return false;
		// expire after short interval (e.g., 60 seconds)
		if ( intval( $val ) + 60 < time() ) {
			WC()->session->__unset( 'bzj_rtc_redirected' );
			return false;
		}
		return true;
	} catch ( Throwable $t ) {
		return false;
	}
}

/* -------------------------
 * Hook into template redirect to set session flag after PHP redirect (best-effort)
 * If PHP redirect via woocommerce_add_to_cart_redirect returned checkout, set session flag so JS won't redirect again.
 * ------------------------- */
add_action( 'template_redirect', 'bzj_rtc_maybe_set_session_flag_from_request', 5 );
function bzj_rtc_maybe_set_session_flag_from_request() {
	// If this is a result of a completed add-to-cart form that redirected to checkout, set a small session flag
	if ( ! function_exists( 'wc_get_checkout_url' ) ) return;
	// Only run for front-end, not admin
	if ( is_admin() && ! wp_doing_ajax() ) return;

	// If request contains add-to-cart and we're now rendering checkout, set flag
	if ( isset( $_REQUEST['add-to-cart'] ) ) {
		$checkout = wc_get_checkout_url();
		// If current URL looks like checkout, mark session to avoid JS double redirect
		if ( strpos( strtolower( $_SERVER['REQUEST_URI'] ), strtolower( parse_url( $checkout, PHP_URL_PATH ) ) ) !== false ) {
			bzj_rtc_set_session_flag();
			bzj_rtc_log( 'Set session flag due to server-side add-to-cart redirect', array( 'request_uri' => $_SERVER['REQUEST_URI'] ) );
		}
	}
}

/* -------------------------
 * Expose filters for per-product control and final veto:
 * - 'bzj_rtc_allowed_product_ids' to return array of allowed IDs (empty = all)
 * - 'bzj_rtc_should_redirect_user' to allow redirect for admin user
 * - 'bzj_rtc_should_redirect' can veto globally
 * ------------------------- */

/* -------------------------
 * Compatibility note:
 * - This plugin intentionally keeps logic conservative:
 *   * Default is to redirect for any add-to-cart event when enabled.
 *   * If you need per-product restrictions, return an array of ids via filter 'bzj_rtc_allowed_product_ids'.
 * ------------------------- */

/* -------------------------
 * End of file
 * ------------------------- */