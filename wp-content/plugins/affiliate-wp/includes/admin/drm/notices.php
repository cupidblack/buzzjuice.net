<?php
/**
 * DRM Notices
 *
 * Must return an array of notices. Example:
 *
 *     array(
 *          'notice_id' => array(
 *              'message' => 'The notice content.'
 *          )
 *     )
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
 * Retrieves an array of DRM notices to be displayed.
 *
 * In order to display notices, you need to return an array with the {state}_{level}. Eg.:
 *
 * 'unlicensed_low_level' => array(
 *     'level' => 'error',
 *     'message' => __( 'Unlicensed Low Level Message', 'affiliate-wp' ),
 * ),
 *
 * The level property can be any notice level described here: https://developer.wordpress.org/reference/hooks/admin_notices/
 * Possible values: info, error, warning, success. Default: error.
 *
 * The message property can return both strings or functions that return a string.
 *
 * @return array Array of notices to be displayed.
 */

try {
	$icon_tag = \AffiliateWP\Utils\Icons::to_base64( 'exclamation-triangle' );

	if ( ! empty( $icon_tag ) ) {
		$icon_tag = sprintf(
			'<img src="%s" width="24.067" height="24" alt="%s">',
			$icon_tag,
			esc_html( __( 'Exclamation Icon', 'affiliate-wp' ) )
		);
	}
} catch ( \Exception $e ) {
	$icon_tag = '';
}

return array(

	// Unlicensed: Day 0-13. Gentle nudge.
	'unlicensed_initiated' => array(
		'level'   => 'info',
		'message' => function() {
			ob_start();
			?>
			<p>
				<strong><?php esc_html_e( 'AffiliateWP is not activated.', 'affiliate-wp' ); ?></strong>
				<?php
				printf(
					/* translators: %s - Link to the settings screen */
					esc_html__( '%s to enable updates, new features, and support.', 'affiliate-wp' ),
					sprintf(
						'<a href="%s">%s</a>',
						esc_url( sprintf( '%s#license_key', affwp_admin_url( 'settings' ) ) ),
						esc_html__( 'Enter your license key', 'affiliate-wp' )
					)
				);
				?>
			</p>
			<?php
			return ob_get_clean();
		},
	),

	// Unlicensed: Day 14-20. Escalation.
	'unlicensed_low_level' => array(
		'level'   => 'warning',
		'message' => function() {
			ob_start();
			?>
			<p>
				<strong><?php esc_html_e( 'Your AffiliateWP license is missing.', 'affiliate-wp' ); ?></strong>
				<?php esc_html_e( 'Without an active license, you will lose access to affiliate management, payouts, and plugin updates.', 'affiliate-wp' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_license_key_field_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Enter license key', 'affiliate-wp' ); ?></a>
			</p>
			<?php
			return ob_get_clean();
		},
	),

	// Unlicensed: Day 21-29. Shown on non-AffWP pages (modal handles AffWP pages).
	'unlicensed_med_level' => array(
		'message' => function() {
			ob_start();
			?>
			<p>
				<strong><?php esc_html_e( 'AffiliateWP will be locked soon.', 'affiliate-wp' ); ?></strong>
				<?php esc_html_e( 'Enter your license key to avoid losing access to your affiliate program.', 'affiliate-wp' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_license_key_field_url() ); ?>" class="button button-primary"><?php esc_html_e( 'Enter license key', 'affiliate-wp' ); ?></a>
			</p>
			<?php
			return ob_get_clean();
		},
	),

	// Invalid (expired): Day 0-6. First touchpoint after expiry.
	'invalid_initiated'    => array(
		'level'   => 'warning',
		'message' => function() {
			ob_start();
			?>
			<p>
				<strong><?php esc_html_e( 'Your AffiliateWP license has expired.', 'affiliate-wp' ); ?></strong>
				<?php esc_html_e( 'Renew to keep receiving updates, new features, and priority support.', 'affiliate-wp' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_utm_link( 'account' ) ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Renew your license', 'affiliate-wp' ); ?></a>
			</p>
			<?php
			return ob_get_clean();
		},
	),

	// Invalid (expired): Day 7-20. Shown on non-AffWP pages (modal handles AffWP pages).
	'invalid_med_level'    => array(
		'message' => function() {
			ob_start();
			?>
			<p>
				<strong><?php esc_html_e( 'AffiliateWP will be locked soon.', 'affiliate-wp' ); ?></strong>
				<?php esc_html_e( 'Your license has expired. Renew now to avoid losing access to your affiliate program.', 'affiliate-wp' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_attr( affiliate_wp()->drm->get_utm_link( 'account' ) ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'Renew your license', 'affiliate-wp' ); ?></a>
			</p>
			<?php
			return ob_get_clean();
		},
	),
);
