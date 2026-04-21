<?php
/**
 * Admin Options Settings Template
 *
 * Renders the main settings form with accordion sections for:
 * - Email Verification Settings
 * - General Settings
 * - Login Authentication Settings
 *
 * This template is included by the admin class and expects
 * the following methods to be available via $this:
 * - render_settings_fields()
 * - get_cev_settings_data_new()
 * - get_cev_general_settings_data()
 * - get_cev_settings_data_login_otp()
 *
 * @package Customer_Email_Verification
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly to prevent unauthorized access.
}

// Get the current admin page URL for form submission.
$form_action = admin_url( 'admin.php?page=' . ( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' ) );
?>
<section id="cev_content_settings" class="cev_tab_section">
	<form method="post" id="cev_settings_form" action="<?php echo esc_url( $form_action ); ?>" enctype="multipart/form-data">
		<div class="settings_accordion accordion_container">

			<?php
			/**
			 * Email Verification Settings Section
			 *
			 * Contains settings for email verification during signup,
			 * checkout verification, verification types, and related options.
			 */
			?>
			<div class="accordion_set">
				<div class="accordion heading cev-main-settings">
					<div class="accordion-open accordion-label first_label">
						<?php esc_html_e( 'Email verification settings', 'customer-email-verification' ); ?>
					</div>
				</div>
				<div class="panel">
					<?php
					// Render email verification-related settings fields.
					$this->render_settings_fields( $this->get_cev_settings_data_new() );
					?>
				</div>
			</div>

			<?php
			/**
			 * General Settings Section
			 *
			 * Contains general plugin settings such as role-based
			 * verification skipping, email templates, and other
			 * configuration options.
			 */
			?>
			<div class="accordion_set">
				<div class="accordion heading cev-main-settings">
					<div class="accordion-open accordion-label second_label">
						<?php esc_html_e( 'General Settings', 'customer-email-verification' ); ?>
					</div>
				</div>
				<div class="panel">
					<?php
					// Render general settings fields.
					$this->render_settings_fields( $this->get_cev_general_settings_data() );
					?>
				</div>
			</div>

			<?php
			/**
			 * Login Authentication Section
			 *
			 * Contains settings for OTP-based login authentication,
			 * including options for new device detection, location-based
			 * authentication, and login time restrictions.
			 */
			?>
			<div class="accordion_set">
				<div class="accordion heading cev-main-settings">
					<div class="accordion-open accordion-label second_label">
						<?php esc_html_e( 'Login Authentication', 'customer-email-verification' ); ?>
					</div>
				</div>
				<div class="panel">
					<?php
					// Render login authentication settings fields.
					$this->render_settings_fields( $this->get_cev_settings_data_login_otp() );
					?>
				</div>
			</div>

		</div>

		<?php
		/**
		 * Form Submission Section
		 *
		 * Contains the save button and security fields for form validation.
		 */
		?>
		<div class="save_btn">
			<button 
				name="save" 
				class="button-primary woocommerce-save-button cev_settings_save" 
				type="submit" 
				id="cev_settings_save_button"
				aria-label="<?php esc_attr_e( 'Save all settings', 'customer-email-verification' ); ?>"
			>
				<?php esc_html_e( 'Save', 'customer-email-verification' ); ?>
			</button>
		</div>

		<?php
		/**
		 * Security Fields
		 *
		 * Nonce field for CSRF protection and hidden action field
		 * for AJAX form submission handling.
		 */
		wp_nonce_field( 'cev_settings_form_nonce', 'cev_settings_form_nonce' );
		?>
		<input type="hidden" name="action" value="cev_settings_form_update">
	</form>
</section>
