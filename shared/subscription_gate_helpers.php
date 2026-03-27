<?php
/**
 * Buzzjuice Subscription & Gating Helpers
 * Production-grade: powers dashboard gating, widget shortcodes, Elementor role visibility
 */

/* Allowed subscription roles */
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

/* Legacy subscriber check */
if ( ! function_exists( 'bzj_user_has_subscription_role' ) ) {
	function bzj_user_has_subscription_role( $user = null ) {
		if ( is_null( $user ) ) {
			if ( ! is_user_logged_in() ) return false;
			$user = wp_get_current_user();
		} elseif ( is_numeric( $user ) ) {
			$user = get_userdata( (int) $user );
		}
		if ( ! $user || empty( $user->roles ) ) return false;
		return (bool) array_intersect( (array) $user->roles, bzj_allowed_subscription_roles() );
	}
}

/* Helper: Normalize to WP_User */
if ( ! function_exists( 'bz_get_user' ) ) {
	function bz_get_user( $user = null ) {
		if ( is_null( $user ) ) return is_user_logged_in() ? wp_get_current_user() : null;
		if ( is_numeric( $user ) ) return get_userdata( (int) $user );
		return $user;
	}
}

/* User type classifier: guest | general | primary | affiliate | admin */
if ( ! function_exists( 'bz_user_type' ) ) {
	function bz_user_type( $user = null ) {
		$user = bz_get_user( $user );
		if ( ! $user ) return 'guest';
		$roles = (array) $user->roles;
		if ( in_array( 'administrator', $roles ) ) return 'admin';
		if ( in_array( 'jewel_affiliate', $roles ) ) return 'affiliate';
		if ( array_intersect(
			array('classic_lifestyle', 'silver_lifestyle', 'rockstar_lifestyle', 'premium_lifestyle'), $roles
		) ) return 'primary';
		return 'general';
	}
}

/* Check for access (logic only, no rendering) */
if ( ! function_exists( 'bz_user_can' ) ) {
	function bz_user_can( $required_type, $user = null ) {
		$user_type = bz_user_type( $user );
		if ( $user_type === 'admin' ) return true;
		switch ( $required_type ) {
			case 'public': return true;
			case 'logged_in': return is_user_logged_in();
			case 'general': return in_array( $user_type, array('general','primary','affiliate') );
			case 'primary': return in_array( $user_type, array('primary','affiliate') );
			case 'affiliate': return ( $user_type === 'affiliate' );
			default: return false;
		}
	}
}

/* Universal gating: echo or return (for shortcodes) */
if ( ! function_exists( 'bz_gate' ) ) {
	function bz_gate( $required_type, $callback, $args = array(), $echo = true ) {
		$allowed = bz_user_can( $required_type );
		if ( $allowed ) {
			$output = call_user_func( $callback, $args );
		} else {
			$output = bz_render_locked_block( $required_type );
		}
		if ( $echo ) { echo $output; } else { return $output; }
	}
}

/* Render locked overlay/CTA */
if ( ! function_exists( 'bz_render_locked_block' ) ) {
	function bz_render_locked_block( $type = 'primary' ) {
		$messages = array(
			'logged_in' => 'Login to access this feature.',
			'general'   => 'Create a free account to unlock this feature.',
			'primary'   => 'Activate a subscription to view your lifestyle matches.',
			'affiliate' => 'Become an affiliate to view full affiliate stats.',
		);
		$cta_links = array(
			'logged_in' => '/login',
			'general'   => '/register',
			'primary'   => '/upgrade',
			'affiliate' => '/affiliate-program',
		);
		$msg  = isset($messages[$type]) ? $messages[$type] : 'Access restricted.';
		$link = isset($cta_links[$type]) ? $cta_links[$type] : '/';
		ob_start(); ?>
		<div class="bz-locked-box" data-gate="<?php echo esc_attr($type); ?>">
			<p><?php echo esc_html($msg); ?></p>
			<a href="<?php echo esc_url($link); ?>" class="bz-gate-btn">Unlock Now</a>
		</div>
		<?php
		return ob_get_clean();
	}
}

/* (Optional for cross-platform future sync) */
if ( ! function_exists( 'bz_sync_external_user_type' ) ) {
	function bz_sync_external_user_type( $user_id ) {
		$type = bz_user_type( $user_id );
		// Placeholder: integrate with WoWonder/QuickDate.
		do_action( 'bz_after_user_type_detected', $user_id, $type );
	}
}