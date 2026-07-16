<?php
/**
 * DRM Emails
 *
 * @package     AffiliateWP
 * @subpackage  AffiliateWP\Admin\DRM
 * @copyright   Copyright (c) 2023, Awesome Motive, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.21.1
 * @author      Darvin da Silveira <ddasilveira@awesomeomotive.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the recipient's first name for email greeting.
 *
 * @since 2.32.0
 *
 * @return string First name or empty string.
 */
if ( ! function_exists( 'affwp_drm_get_recipient_name' ) ) :
function affwp_drm_get_recipient_name() {

	$email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_option( 'admin_email' ) );
	$user  = get_user_by( 'email', $email );

	if ( $user && ! empty( $user->first_name ) ) {
		return $user->first_name;
	}

	if ( $user && ! empty( $user->display_name ) ) {
		return $user->display_name;
	}

	return '';
}
endif;

/**
 * Get an email-safe CTA button.
 *
 * Uses inline styles for maximum email client compatibility.
 *
 * @since 2.32.0
 *
 * @param string $url  Button URL.
 * @param string $text Button text.
 *
 * @return string HTML button.
 */
if ( ! function_exists( 'affwp_drm_email_button' ) ) :
function affwp_drm_email_button( $url, $text ) {

	return sprintf(
		'<p style="margin: 32px 0 24px 0; text-align: center;"><a href="%s" style="display: inline-block; background-color: #16a34a; color: #ffffff; font-size: 16px; font-weight: 600; text-decoration: none; padding: 12px 32px; border-radius: 6px;">%s</a></p>',
		esc_url( $url ),
		esc_html( $text )
	);
}
endif;

/**
 * Get a hidden preheader for email inbox preview text.
 *
 * Uses a hidden span followed by whitespace padding to prevent
 * email clients from pulling body content into the preview.
 *
 * @since 2.32.0
 *
 * @param string $text Preheader text (max ~100 chars for best results).
 *
 * @return string Hidden preheader HTML.
 */
if ( ! function_exists( 'affwp_drm_email_preheader' ) ) :
function affwp_drm_email_preheader( $text ) {

	// Zero-width whitespace padding prevents clients from pulling body text into preview.
	$padding = str_repeat( '&#847; &#8199; &#65279; &#847; ', 30 );

	return sprintf(
		'<div style="display:none;font-size:1px;color:#f6f6f6;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">%s%s</div>',
		esc_html( $text ),
		$padding
	);
}
endif;

/**
 * Wrap DRM email content in a div with consistent font sizing.
 *
 * @since 2.32.0
 *
 * @param string $content Email HTML content.
 *
 * @return string Wrapped content.
 */
if ( ! function_exists( 'affwp_drm_email_wrap' ) ) :
function affwp_drm_email_wrap( $content ) {

	return '<div style="font-size: 16px; line-height: 1.6; color: #1f2937;">' . $content . '</div>';
}
endif;

/**
 * Get DRM timing data for emails.
 *
 * Returns the number of days elapsed since the DRM state changed
 * and the number of days remaining until lockout.
 *
 * @since 2.32.0
 *
 * @return array {
 *     @type int    $days_elapsed   Days since state changed.
 *     @type int    $days_remaining Days until lockout.
 *     @type string $state          Current DRM state (invalid or unlicensed).
 * }
 */
if ( ! function_exists( 'affwp_drm_get_timing' ) ) :
function affwp_drm_get_timing() {

	$timestamp = get_option( 'affwp_drm_last_changed_state_time', 0 );
	$state     = get_option( 'affwp_drm_current_state', 'valid' );

	if ( empty( $timestamp ) ) {
		return array(
			'days_elapsed'   => 0,
			'days_remaining' => 0,
			'state'          => $state,
		);
	}

	$start_date   = new \DateTime( current_time( 'Y-m-d' ) );
	$end_date     = new \DateTime( wp_date( 'Y-m-d', $timestamp ) );
	$days_elapsed = absint( $end_date->diff( $start_date )->format( '%a' ) );

	$locked_at = 'invalid' === $state ? 21 : 30;

	return array(
		'days_elapsed'   => $days_elapsed,
		'days_remaining' => max( 0, $locked_at - $days_elapsed ),
		'state'          => $state,
	);
}
endif;

/**
 * Format a day count with proper singular/plural.
 *
 * @since 2.32.0
 *
 * @param int $days Number of days.
 *
 * @return string Formatted day string (e.g. "1 day" or "14 days").
 */
if ( ! function_exists( 'affwp_drm_format_days' ) ) :
function affwp_drm_format_days( $days ) {

	return sprintf(
		_n( '%s day', '%s days', $days, 'affiliate-wp' ),
		number_format_i18n( $days )
	);
}
endif;

/**
 * Retrieves an array of DRM emails.
 *
 * In order to register emails, you need to return an array with the {state}_{level}. Eg.:
 *
 * 'unlicensed_low_level' => array(
 *     'subject' => 'The email subject',
 *     'message' => __( 'Some email content', 'affiliate-wp' ),
 * ),
 *
 * The message property can return both strings or functions that return a string.
 *
 * @return array Array of email contents.
 */
return array(

	/*
	 * Unlicensed — Low Level (day 14+)
	 *
	 * Tone: Helpful, no pressure. First email — they may not realize the key is missing.
	 */
	'unlicensed_low_level' => array(
		'subject' => __( 'AffiliateWP needs a license key', 'affiliate-wp' ),
		'message' => function() {

			$name            = affwp_drm_get_recipient_name();
			$site_name       = get_bloginfo( 'name' );
			$affiliate_count = affiliate_wp()->affiliates->count();
			$pricing_url     = affiliate_wp()->drm->get_utm_link( 'pricing', 'email' );
			$account_url     = affiliate_wp()->drm->get_utm_link( 'account', 'email' );

			ob_start();

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_preheader(
				$affiliate_count > 0
					? sprintf(
						/* translators: %s - affiliate count */
						__( 'Your %s affiliates need an active license to stay managed.', 'affiliate-wp' ),
						number_format_i18n( $affiliate_count )
					)
					: __( 'Add a license key to keep affiliate management, payouts, and updates active.', 'affiliate-wp' )
			);

			?>

			<?php if ( $name ) : ?>
			<p style="margin: 0 0 4px 0;"><?php printf( esc_html__( 'Hi %s,', 'affiliate-wp' ), esc_html( $name ) ); ?></p>
			<?php endif; ?>

			<p style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;"><?php esc_html_e( 'Your license key is missing', 'affiliate-wp' ); ?></p>

			<p><?php
				printf(
					/* translators: %s - site name */
					esc_html__( 'We noticed AffiliateWP on %s doesn\'t have a license key yet.', 'affiliate-wp' ),
					esc_html( $site_name )
				);
			?></p>

			<?php if ( $affiliate_count > 0 ) : ?>
			<p><?php
				printf(
					/* translators: %s - affiliate count */
					esc_html__( 'You have %s affiliates depending on this plugin. Without a license, you\'ll eventually lose access to:', 'affiliate-wp' ),
					number_format_i18n( $affiliate_count )
				);
			?></p>
			<?php else : ?>
			<p><?php esc_html_e( 'Without a license, you\'ll eventually lose access to:', 'affiliate-wp' ); ?></p>
			<?php endif; ?>

			<ul style="padding-left: 20px; margin: 16px 0;">
				<li style="margin-bottom: 8px;"><?php esc_html_e( 'Affiliate management and reporting', 'affiliate-wp' ); ?></li>
				<li style="margin-bottom: 8px;"><?php esc_html_e( 'Payout processing', 'affiliate-wp' ); ?></li>
				<li style="margin-bottom: 8px;"><?php esc_html_e( 'Plugin updates and new features', 'affiliate-wp' ); ?></li>
				<li><?php esc_html_e( 'Security patches', 'affiliate-wp' ); ?></li>
			</ul>

			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_button( $pricing_url, __( 'Get a license', 'affiliate-wp' ) );
			?>

			<p style="margin-bottom: 0; text-align: center;"><?php esc_html_e( 'Already have a key? Find it on your', 'affiliate-wp' ); ?> <a href="<?php echo esc_url( $account_url ); ?>" class="affwp-email-link" style="color: #4b5563;"><?php esc_html_e( 'account page', 'affiliate-wp' ); ?></a>.</p>

			<?php

			return affwp_drm_email_wrap( ob_get_clean() );
		},
	),

	/*
	 * Unlicensed — Medium Level (day 21+)
	 *
	 * Tone: Urgent. Countdown to lockout. Personalized with real data.
	 */
	'unlicensed_med_level' => array(
		'subject' => function() {
			$timing = affwp_drm_get_timing();

			return sprintf(
				/* translators: %s - number of days */
				__( 'AffiliateWP locks in %s', 'affiliate-wp' ),
				affwp_drm_format_days( $timing['days_remaining'] )
			);
		},
		'message' => function() {

			$name               = affwp_drm_get_recipient_name();
			$site_name          = get_bloginfo( 'name' );
			$timing             = affwp_drm_get_timing();
			$affiliate_count    = affiliate_wp()->affiliates->count();
			$pending_affiliates = affiliate_wp()->affiliates->count( array( 'status' => 'pending' ) );
			$unpaid_referrals   = affiliate_wp()->referrals->count_by_status( 'unpaid' );
			$unpaid_amount      = affiliate_wp()->referrals->unpaid_earnings( '', 0, false );
			$unpaid_formatted   = affwp_currency_filter( affwp_format_amount( $unpaid_amount ) );
			$pricing_url        = affiliate_wp()->drm->get_utm_link( 'pricing', 'email' );
			$account_url        = affiliate_wp()->drm->get_utm_link( 'account', 'email' );

			ob_start();

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_preheader(
				sprintf(
					/* translators: %s - days remaining */
					__( 'You have %s before you lose access to affiliate management and payouts.', 'affiliate-wp' ),
					affwp_drm_format_days( $timing['days_remaining'] )
				)
			);

			?>

			<?php if ( $name ) : ?>
			<p style="margin: 0 0 4px 0;"><?php printf( esc_html__( 'Hi %s,', 'affiliate-wp' ), esc_html( $name ) ); ?></p>
			<?php endif; ?>

			<p style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;"><?php esc_html_e( 'AffiliateWP will be locked soon', 'affiliate-wp' ); ?></p>

			<p><?php
				printf(
					/* translators: 1: site name, 2: days elapsed, 3: days remaining */
					esc_html__( 'AffiliateWP on %1$s has been running without a license for %2$s. You have %3$s to add one before you lose access to your affiliate program.', 'affiliate-wp' ),
					esc_html( $site_name ),
					affwp_drm_format_days( $timing['days_elapsed'] ),
					affwp_drm_format_days( $timing['days_remaining'] )
				);
			?></p>

			<p><strong><?php esc_html_e( "Here's what's at risk:", 'affiliate-wp' ); ?></strong></p>

			<ul style="padding-left: 20px; margin: 16px 0;">
				<?php if ( $affiliate_count > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - affiliate count */
						esc_html__( 'Manage your %s affiliates', 'affiliate-wp' ),
						number_format_i18n( $affiliate_count )
					);
				?></li>
				<?php endif; ?>

				<?php if ( $pending_affiliates > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - pending count */
						esc_html__( 'Review %s pending affiliate applications', 'affiliate-wp' ),
						number_format_i18n( $pending_affiliates )
					);
				?></li>
				<?php endif; ?>

				<?php if ( $unpaid_referrals > 0 && $unpaid_amount > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: 1: currency amount, 2: referral count */
						esc_html__( 'Pay out %1$s across %2$s referrals', 'affiliate-wp' ),
						esc_html( $unpaid_formatted ),
						number_format_i18n( $unpaid_referrals )
					);
				?></li>
				<?php elseif ( $unpaid_referrals > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - referral count */
						esc_html__( 'Process %s unpaid referrals', 'affiliate-wp' ),
						number_format_i18n( $unpaid_referrals )
					);
				?></li>
				<?php endif; ?>

				<li style="margin-bottom: 8px;"><?php esc_html_e( 'Access reports, updates, and security patches', 'affiliate-wp' ); ?></li>
			</ul>

			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_button( $pricing_url, __( 'Get a license now', 'affiliate-wp' ) );
			?>

			<p style="margin-bottom: 0; text-align: center;"><?php esc_html_e( 'Already have a key? Find it on your', 'affiliate-wp' ); ?> <a href="<?php echo esc_url( $account_url ); ?>" class="affwp-email-link" style="color: #4b5563;"><?php esc_html_e( 'account page', 'affiliate-wp' ); ?></a>.</p>

			<?php

			return affwp_drm_email_wrap( ob_get_clean() );
		},
	),

	/*
	 * Unlicensed — Locked (day 30+)
	 *
	 * Tone: Final. Locked. Emphasize what they are losing right now.
	 */
	'unlicensed_locked'    => array(
		'subject' => __( 'AffiliateWP has been locked', 'affiliate-wp' ),
		'message' => function() {

			$name               = affwp_drm_get_recipient_name();
			$site_name          = get_bloginfo( 'name' );
			$timing             = affwp_drm_get_timing();
			$affiliate_count    = affiliate_wp()->affiliates->count();
			$pending_affiliates = affiliate_wp()->affiliates->count( array( 'status' => 'pending' ) );
			$unpaid_referrals   = affiliate_wp()->referrals->count_by_status( 'unpaid' );
			$unpaid_amount      = affiliate_wp()->referrals->unpaid_earnings( '', 0, false );
			$unpaid_formatted   = affwp_currency_filter( affwp_format_amount( $unpaid_amount ) );
			$pricing_url        = affiliate_wp()->drm->get_utm_link( 'pricing', 'email' );
			$account_url        = affiliate_wp()->drm->get_utm_link( 'account', 'email' );

			ob_start();

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_preheader(
				__( 'Your affiliate program can no longer be managed. Add a license to restore access.', 'affiliate-wp' )
			);

			?>

			<?php if ( $name ) : ?>
			<p style="margin: 0 0 4px 0;"><?php printf( esc_html__( 'Hi %s,', 'affiliate-wp' ), esc_html( $name ) ); ?></p>
			<?php endif; ?>

			<p style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;"><?php esc_html_e( 'AffiliateWP has been locked', 'affiliate-wp' ); ?></p>

			<p><?php
				printf(
					/* translators: 1: site name, 2: days elapsed */
					esc_html__( 'AffiliateWP on %1$s has been running without a license for %2$s. We provided a 30-day grace period, and that window has now closed.', 'affiliate-wp' ),
					esc_html( $site_name ),
					affwp_drm_format_days( $timing['days_elapsed'] )
				);
			?></p>

			<p><?php esc_html_e( 'Your website and affiliate links still work, but you can no longer:', 'affiliate-wp' ); ?></p>

			<ul style="padding-left: 20px; margin: 16px 0;">
				<?php if ( $affiliate_count > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - affiliate count */
						esc_html__( 'Manage your %s affiliates', 'affiliate-wp' ),
						number_format_i18n( $affiliate_count )
					);
				?></li>
				<?php endif; ?>

				<?php if ( $pending_affiliates > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - pending count */
						esc_html__( 'Review %s pending affiliate applications', 'affiliate-wp' ),
						number_format_i18n( $pending_affiliates )
					);
				?></li>
				<?php endif; ?>

				<?php if ( $unpaid_referrals > 0 && $unpaid_amount > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: 1: currency amount, 2: referral count */
						esc_html__( 'Pay out %1$s across %2$s referrals', 'affiliate-wp' ),
						esc_html( $unpaid_formatted ),
						number_format_i18n( $unpaid_referrals )
					);
				?></li>
				<?php endif; ?>

				<li style="margin-bottom: 8px;"><?php esc_html_e( 'Access reports, updates, and security patches', 'affiliate-wp' ); ?></li>
			</ul>

			<p style="font-size: 14px; color: #6b7280; font-style: italic;"><?php esc_html_e( 'Adding a license takes less than a minute and restores access immediately.', 'affiliate-wp' ); ?></p>

			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_button( $pricing_url, __( 'Restore access now', 'affiliate-wp' ) );
			?>

			<p style="margin-bottom: 0; text-align: center;"><?php esc_html_e( 'Already have a key? Find it on your', 'affiliate-wp' ); ?> <a href="<?php echo esc_url( $account_url ); ?>" class="affwp-email-link" style="color: #4b5563;"><?php esc_html_e( 'account page', 'affiliate-wp' ); ?></a>.</p>

			<?php

			return affwp_drm_email_wrap( ob_get_clean() );
		},
	),

	/*
	 * Invalid (expired) — Medium Level (day 7+)
	 *
	 * Tone: Urgent. Their license expired — they were a paying customer. Emphasize renewal.
	 */
	'invalid_med_level'    => array(
		'subject' => function() {
			$timing = affwp_drm_get_timing();

			return sprintf(
				/* translators: %s - number of days */
				__( 'License expired — locks in %s', 'affiliate-wp' ),
				affwp_drm_format_days( $timing['days_remaining'] )
			);
		},
		'message' => function() {

			$name               = affwp_drm_get_recipient_name();
			$site_name          = get_bloginfo( 'name' );
			$timing             = affwp_drm_get_timing();
			$affiliate_count    = affiliate_wp()->affiliates->count();
			$pending_affiliates = affiliate_wp()->affiliates->count( array( 'status' => 'pending' ) );
			$unpaid_referrals   = affiliate_wp()->referrals->count_by_status( 'unpaid' );
			$unpaid_amount      = affiliate_wp()->referrals->unpaid_earnings( '', 0, false );
			$unpaid_formatted   = affwp_currency_filter( affwp_format_amount( $unpaid_amount ) );
			$account_url        = affiliate_wp()->drm->get_utm_link( 'account', 'email' );

			ob_start();

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_preheader(
				sprintf(
					/* translators: %s - days remaining */
					__( 'Renew in the next %s to keep managing your affiliates and payouts.', 'affiliate-wp' ),
					affwp_drm_format_days( $timing['days_remaining'] )
				)
			);

			?>

			<?php if ( $name ) : ?>
			<p style="margin: 0 0 4px 0;"><?php printf( esc_html__( 'Hi %s,', 'affiliate-wp' ), esc_html( $name ) ); ?></p>
			<?php endif; ?>

			<p style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;"><?php esc_html_e( 'Your license has expired', 'affiliate-wp' ); ?></p>

			<p><?php
				printf(
					/* translators: 1: site name, 2: days elapsed, 3: days remaining */
					esc_html__( 'Your AffiliateWP license on %1$s expired %2$s ago. You have %3$s to renew before you lose access to your affiliate program.', 'affiliate-wp' ),
					esc_html( $site_name ),
					affwp_drm_format_days( $timing['days_elapsed'] ),
					affwp_drm_format_days( $timing['days_remaining'] )
				);
			?></p>

			<p><strong><?php esc_html_e( "Without renewal, you won't be able to:", 'affiliate-wp' ); ?></strong></p>

			<ul style="padding-left: 20px; margin: 16px 0;">
				<?php if ( $affiliate_count > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - affiliate count */
						esc_html__( 'Manage your %s affiliates', 'affiliate-wp' ),
						number_format_i18n( $affiliate_count )
					);
				?></li>
				<?php endif; ?>

				<?php if ( $pending_affiliates > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - pending count */
						esc_html__( 'Review %s pending affiliate applications', 'affiliate-wp' ),
						number_format_i18n( $pending_affiliates )
					);
				?></li>
				<?php endif; ?>

				<?php if ( $unpaid_referrals > 0 && $unpaid_amount > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: 1: currency amount, 2: referral count */
						esc_html__( 'Pay out %1$s across %2$s referrals', 'affiliate-wp' ),
						esc_html( $unpaid_formatted ),
						number_format_i18n( $unpaid_referrals )
					);
				?></li>
				<?php elseif ( $unpaid_referrals > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - referral count */
						esc_html__( 'Process %s unpaid referrals', 'affiliate-wp' ),
						number_format_i18n( $unpaid_referrals )
					);
				?></li>
				<?php endif; ?>

				<li style="margin-bottom: 8px;"><?php esc_html_e( 'Access reports, updates, and security patches', 'affiliate-wp' ); ?></li>
			</ul>

			<p style="font-size: 14px; color: #6b7280; font-style: italic;"><?php esc_html_e( 'Renewing takes less than a minute.', 'affiliate-wp' ); ?></p>

			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_button( $account_url, __( 'Renew your license', 'affiliate-wp' ) );
			?>

			<?php

			return affwp_drm_email_wrap( ob_get_clean() );
		},
	),

	/*
	 * Invalid (expired) — Locked (day 21+)
	 *
	 * Tone: Final. Locked. They were a customer — emphasize restoring access.
	 */
	'invalid_locked'       => array(
		'subject' => __( 'AffiliateWP locked — renew now', 'affiliate-wp' ),
		'message' => function() {

			$name               = affwp_drm_get_recipient_name();
			$site_name          = get_bloginfo( 'name' );
			$timing             = affwp_drm_get_timing();
			$affiliate_count    = affiliate_wp()->affiliates->count();
			$pending_affiliates = affiliate_wp()->affiliates->count( array( 'status' => 'pending' ) );
			$unpaid_referrals   = affiliate_wp()->referrals->count_by_status( 'unpaid' );
			$unpaid_amount      = affiliate_wp()->referrals->unpaid_earnings( '', 0, false );
			$unpaid_formatted   = affwp_currency_filter( affwp_format_amount( $unpaid_amount ) );
			$account_url        = affiliate_wp()->drm->get_utm_link( 'account', 'email' );

			ob_start();

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_preheader(
				__( 'Your affiliate program can no longer be managed. Renew to restore access immediately.', 'affiliate-wp' )
			);

			?>

			<?php if ( $name ) : ?>
			<p style="margin: 0 0 4px 0;"><?php printf( esc_html__( 'Hi %s,', 'affiliate-wp' ), esc_html( $name ) ); ?></p>
			<?php endif; ?>

			<p style="font-size: 20px; font-weight: 700; margin: 0 0 16px 0;"><?php esc_html_e( 'AffiliateWP has been locked', 'affiliate-wp' ); ?></p>

			<p><?php
				printf(
					/* translators: 1: site name, 2: days elapsed */
					esc_html__( 'Your AffiliateWP license on %1$s expired %2$s ago. We provided a 21-day grace period, and that window has now closed.', 'affiliate-wp' ),
					esc_html( $site_name ),
					affwp_drm_format_days( $timing['days_elapsed'] )
				);
			?></p>

			<p><?php esc_html_e( 'Your website and affiliate links still work, but you can no longer:', 'affiliate-wp' ); ?></p>

			<ul style="padding-left: 20px; margin: 16px 0;">
				<?php if ( $affiliate_count > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - affiliate count */
						esc_html__( 'Manage your %s affiliates', 'affiliate-wp' ),
						number_format_i18n( $affiliate_count )
					);
				?></li>
				<?php endif; ?>

				<?php if ( $pending_affiliates > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: %s - pending count */
						esc_html__( 'Review %s pending affiliate applications', 'affiliate-wp' ),
						number_format_i18n( $pending_affiliates )
					);
				?></li>
				<?php endif; ?>

				<?php if ( $unpaid_referrals > 0 && $unpaid_amount > 0 ) : ?>
				<li style="margin-bottom: 8px;"><?php
					printf(
						/* translators: 1: currency amount, 2: referral count */
						esc_html__( 'Pay out %1$s across %2$s referrals', 'affiliate-wp' ),
						esc_html( $unpaid_formatted ),
						number_format_i18n( $unpaid_referrals )
					);
				?></li>
				<?php endif; ?>

				<li style="margin-bottom: 8px;"><?php esc_html_e( 'Access reports, updates, and security patches', 'affiliate-wp' ); ?></li>
			</ul>

			<p style="font-size: 14px; color: #6b7280; font-style: italic;"><?php esc_html_e( 'Renewing takes less than a minute and restores access immediately.', 'affiliate-wp' ); ?></p>

			<?php
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Helper returns escaped HTML.
			echo affwp_drm_email_button( $account_url, __( 'Renew your license now', 'affiliate-wp' ) );
			?>

			<?php

			return affwp_drm_email_wrap( ob_get_clean() );
		},
	),
);
