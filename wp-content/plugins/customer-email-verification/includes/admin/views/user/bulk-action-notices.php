<?php
/**
 * Bulk Action Notices Template
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Handle notice for verified users.
if ( ! empty( $_REQUEST['verify_users_emails'] ) ) {
	$verify_users_emails = absint( wc_clean( $_REQUEST['verify_users_emails'] ) );
	?>
	<div id="message" class="updated notice is-dismissible">
		<p>
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: Number of users whose verification status was updated */
					_n(
						'Verification status updated for %s user.',
						'Verification status updated for %s users.',
						$verify_users_emails,
						'customer-email-verification'
					),
					$verify_users_emails
				)
			);
			?>
		</p>
	</div>
	<?php
}
