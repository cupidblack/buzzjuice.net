<?php
/**
 * Admin Views: Emails Tab
 *
 * @package     AffiliateWP
 * @subpackage  Admin/Emails
 * @copyright   Copyright (c) 2025, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.29.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the emails controller instance.
global $affwp_emails_tab;
if ( ! isset( $affwp_emails_tab ) ) {
	$affwp_emails_tab = new AffiliateWP_Admin_Emails_Tab();
}
$emails_controller = $affwp_emails_tab;
$email_groups      = $emails_controller->get_email_groups();

// Get the affiliate manager email.
$affiliate_manager_email = affiliate_wp()->settings->get( 'affiliate_manager_email', get_bloginfo( 'admin_email' ) );

// Get general email settings.
$email_logo                = affiliate_wp()->settings->get( 'email_logo', '' );
$email_template            = affiliate_wp()->settings->get( 'email_template', 'default' );
$from_name                 = affiliate_wp()->settings->get( 'from_name', get_bloginfo( 'name' ) );
$from_email                = affiliate_wp()->settings->get( 'from_email', get_bloginfo( 'admin_email' ) );
$affiliate_email_summaries = affiliate_wp()->settings->get( 'affiliate_email_summaries', false );

// Get available email templates.
$email_templates = affwp_get_email_templates();

// Check if Stripe Payouts is configured and enabled.
$stripe_enabled = function_exists( 'affwp_stripe_payouts_is_configured' ) && affwp_stripe_payouts_is_configured();

// Check if webhooks are configured.
$webhooks_configured = function_exists( 'affwp_stripe_payouts_get_webhook_secret' ) && ! empty( affwp_stripe_payouts_get_webhook_secret() );

// Check if approval is required for affiliate registration.
$approval_required = affiliate_wp()->settings->get( 'require_approval' );

// Prepare all PHP variables for JavaScript.
$email_config = [
	'currentUserEmail'      => wp_get_current_user()->user_email,
	'emailLogo'             => $email_logo,
	'emailSummariesEnabled' => $affiliate_email_summaries,
	'webhooksConfigured'    => $webhooks_configured,
	'approvalRequired'      => $approval_required,
	'nonces'                => [
		'testEmail'    => wp_create_nonce( 'affwp_test_email' ),
		'previewEmail' => wp_create_nonce( 'affwp_preview_email' ),
	],
	'toggles'               => [],
];

// Build toggle variables and panel state variables.
foreach ( $email_groups as $group ) {
	if ( isset( $group['subgroups'] ) ) {
		foreach ( $group['subgroups'] as $subgroup ) {
			if ( isset( $subgroup['emails'] ) ) {
				foreach ( $subgroup['emails'] as $email_id => $email ) {
					$toggle_key                             = 'toggle_' . str_replace( '-', '_', $email_id );
					$email_config['toggles'][ $toggle_key ] = $email['enabled'];
					// Add panel state variable for each email.
					$panel_key                              = 'emailPanel_' . str_replace( '-', '_', $email_id );
					$email_config['toggles'][ $panel_key ]  = false;
				}
			}
		}
	}
}

/**
 * Get the content for the webhook configuration modal
 *
 * @since 2.29.0
 * @return string The modal content HTML.
 */
function affwp_emails_get_webhook_modal_content() {
	// Get the check-circle icon with classes
	$check_icon = \AffiliateWP\Utils\Icons::get( 'check-circle', [ 'class' => 'w-5 h-5 text-green-600' ] );

	ob_start();
	?>
	<div class="space-y-4">
		<p class="text-base text-gray-700"><?php esc_html_e( 'Enable webhooks to unlock automatic email notifications when important events occur in Stripe:', 'affiliate-wp' ); ?></p>
		<div class="space-y-3">
			<div class="flex items-center">
				<?php echo $check_icon; ?>
				<span class="ml-3 text-base text-gray-700"><?php esc_html_e( 'Real-time payout notifications', 'affiliate-wp' ); ?></span>
			</div>
			<div class="flex items-center">
				<?php echo $check_icon; ?>
				<span class="ml-3 text-base text-gray-700"><?php esc_html_e( 'Automatic emails for successful transfers', 'affiliate-wp' ); ?></span>
			</div>
			<div class="flex items-center">
				<?php echo $check_icon; ?>
				<span class="ml-3 text-base text-gray-700"><?php esc_html_e( 'Instant failure alerts for quick resolution', 'affiliate-wp' ); ?></span>
			</div>
			<div class="flex items-center">
				<?php echo $check_icon; ?>
				<span class="ml-3 text-base text-gray-700"><?php esc_html_e( 'Secure, encrypted communication with Stripe', 'affiliate-wp' ); ?></span>
			</div>
		</div>
		<p class="text-sm text-gray-600 mt-4"><?php esc_html_e( 'Without webhooks configured, these email notifications cannot be sent automatically.', 'affiliate-wp' ); ?></p>
	</div>
	<?php
	return ob_get_clean();
}
?>

<script>
// Pass PHP configuration to JavaScript
const affwpEmailConfig = <?php echo wp_json_encode( $email_config ); ?>;

// Define Alpine component
document.addEventListener('alpine:init', () => {
	Alpine.data('emailSettings', () => ({
		init() {
			// Listen for send test email event from modal
			window.addEventListener('send-test-email', (e) => {
				if (e.detail && e.detail.emailType) {
					this.sendTestEmail(e.detail.emailType);
				}
			});

			// Initialize tag insertion tracking
			this.initializeTagInsertion();

			// Initialize toggle states from config
			Object.assign(this, affwpEmailConfig.toggles);
		},

		// Section collapse states with persistence
		generalSettingsOpen: Alpine.$persist(true).as('affwp_general_settings_open'),
		affiliateWPEmailsOpen: Alpine.$persist(true).as('affwp_core_emails_open'),
		stripeEmailsOpen: Alpine.$persist(false).as('affwp_stripe_emails_open'),
		customEmailsOpen: Alpine.$persist(false).as('affwp_custom_emails_open'),

		// Email states
		testingEmail: null,
		previewingEmail: null,
		testRecipient: affwpEmailConfig.currentUserEmail,
		toastMessage: '',
		toastType: 'success',
		toastVisible: false,
		toastTimeout: null,
		emailLogo: affwpEmailConfig.emailLogo,
		emailSummariesEnabled: affwpEmailConfig.emailSummariesEnabled,

		// Tag insertion tracking.
		lastFocusedField: null,
		lastFocusedEditor: null,
		cursorPositions: {},

		// Add all toggle properties from config.
		...affwpEmailConfig.toggles,

		toggleAccordion(emailId, event) {
			// Prevent form submission
			event.preventDefault();
			event.stopPropagation();

			// Toggle accordion using individual panel variables.
			const panelKey = 'emailPanel_' + emailId.replace(/-/g, '_');
			// Close all other panels first
			Object.keys(this.$data).forEach(key => {
				if (key.startsWith('emailPanel_') && key !== panelKey) {
					this[key] = false;
				}
			});
			// Toggle the current panel.
			if (this[panelKey] !== undefined) {
				this[panelKey] = !this[panelKey];

				// Focus panel when opened for escape key support.
				if (this[panelKey]) {
					this.$nextTick(() => {
						const panel = document.getElementById('email-' + emailId + '-panel');
						if (panel) {
							panel.focus();
						}
					});
				}
			}
		},

		handleWebhookToggle(emailId, webhookRequired, event) {
			// If webhooks are required and not configured, show modal
			if (webhookRequired && !affwpEmailConfig.webhooksConfigured) {
				event.preventDefault();
				event.stopPropagation();
				// Reset the toggle to its original state
				const toggleVar = 'toggle_' + emailId.replace(/-/g, '_');
				this[toggleVar] = !this[toggleVar];
				// Open the webhook configuration modal
				Alpine.store('modals').open('webhook-configuration-modal');
				return false;
			}
			// Otherwise allow normal toggle behavior
			return true;
		},

		handleApprovalToggle(emailId, event) {
			// If approval is not enabled, show modal
			if (!affwpEmailConfig.approvalRequired) {
				event.preventDefault();
				event.stopPropagation();
				// Reset the toggle to its original state
				const toggleVar = 'toggle_' + emailId.replace(/-/g, '_');
				this[toggleVar] = !this[toggleVar];
				// Open the approval configuration modal
				Alpine.store('modals').open('approval-configuration-modal');
				return false;
			}
			// Otherwise allow normal toggle behavior
			return true;
		},

		openMediaLibrary() {
			if (typeof wp !== 'undefined' && wp.media) {
				const mediaFrame = wp.media({
					title: 'Select or Upload Logo',
					button: {
						text: 'Use this logo'
					},
				multiple: false,
				library: {
					type: 'image'
				}
				});

				mediaFrame.on('select', () => {
					const attachment = mediaFrame.state().get('selection').first().toJSON();
					this.emailLogo = attachment.url;
					document.getElementById('affwp_settings[email_logo]').value = attachment.url;
				});

				mediaFrame.open();
			}
		},

		async sendTestEmail(emailId, event) {
		this.testingEmail = emailId;

		// Get defaults from button data attributes if event is passed
		let defaultSubject = '';
		let defaultBody = '';
		let bodyField = emailId + '_body'; // Default fallback
		if (event && event.target) {
			const button = event.target.closest('button');
			if (button) {
				defaultSubject = button.dataset.defaultSubject || '';
				defaultBody = button.dataset.defaultBody || '';
				bodyField = button.dataset.bodyField || (emailId + '_body');
			}
		}

		try {
			const formData = new FormData();
			formData.append('action', 'affwp_send_test_email');
			formData.append('email_id', emailId);
			formData.append('recipient', this.testRecipient);
			formData.append('subject', document.getElementById('affwp_settings[' + emailId + '_subject]')?.value || defaultSubject);

			// Get body content - check for TinyMCE first, then fallback to textarea
			let bodyContent = defaultBody;
			if (typeof tinymce !== 'undefined' && tinymce.get('affwp_settings_' + bodyField)) {
				bodyContent = tinymce.get('affwp_settings_' + bodyField).getContent();
			} else if (document.getElementById('affwp_settings[' + bodyField + ']')) {
				bodyContent = document.getElementById('affwp_settings[' + bodyField + ']').value;
			}
			formData.append('body', bodyContent);
			formData.append('nonce', affwpEmailConfig.nonces.testEmail);

			const response = await fetch(ajaxurl, {
				method: 'POST',
				body: formData
			});

			const data = await response.json();

			if (data.success) {
				this.showToast(data.data.message || 'Test email sent successfully!', 'success');
			} else {
				this.showToast(data.data?.message || 'Failed to send test email', 'error');
			}

		} catch (error) {
			console.error('Error sending test email:', error);
			this.showToast('An error occurred while sending the test email', 'error');
		} finally {
			this.testingEmail = null;
		}
	},

	async previewEmail(emailId, defaultSubject, defaultBody) {
		this.previewingEmail = emailId;

		// Get bodyField from the button's data attribute
		let bodyField = emailId + '_body'; // Default fallback
		if (event && event.currentTarget) {
			const button = event.currentTarget;
			bodyField = button.dataset.bodyField || (emailId + '_body');
		}

		// Open modal immediately with loading state
		Alpine.store('modals').open('email-preview-modal', {
			isLoading: true,
			emailType: emailId,
			subject: '',
			from: '',
			bodyHtml: '',
			bodyText: '',
			dataSource: ''
		});

		try {
			const formData = new FormData();
			formData.append('action', 'affwp_preview_email');
			formData.append('email_id', emailId);

			// Get current subject and body values.
			const subjectField = document.getElementById('affwp_settings[' + emailId + '_subject]');
			const subject = subjectField ? subjectField.value : defaultSubject;
			formData.append('subject', subject);

			// Get body content - check for TinyMCE first, then fallback to textarea.
			let bodyContent = defaultBody;
			const bodyFieldId = 'affwp_settings_' + bodyField;
			if (typeof tinymce !== 'undefined' && tinymce.get(bodyFieldId)) {
				bodyContent = tinymce.get(bodyFieldId).getContent();
			} else {
				const bodyFieldElement = document.getElementById('affwp_settings[' + bodyField + ']');
				if (bodyFieldElement) {
					bodyContent = bodyFieldElement.value;
				}
			}
			formData.append('body', bodyContent);
			formData.append('nonce', affwpEmailConfig.nonces.previewEmail);

			const response = await fetch(ajaxurl, {
				method: 'POST',
				body: formData
			});

			const data = await response.json();

			if (data.success) {
				// Update the modal with preview data
				const modal = Alpine.store('modals').registry['email-preview-modal'];
				if (modal) {
					modal.data = {
						isLoading: false,
						emailType: emailId,
						subject: data.data.subject,
						from: data.data.from_name + ' <' + data.data.from_email + '>',
						bodyHtml: data.data.body_html,
						bodyText: data.data.body_text,
						dataSource: data.data.data_source
					};

					// Focus the primary button after content loads
					Alpine.nextTick(() => {
						const modalEl = document.querySelector('[data-modal-id="email-preview-modal"]');
						if (modalEl) {
							// Find the button with autofocus attribute (primary button)
							const sendButton = modalEl.querySelector('button[autofocus]');
							if (sendButton && sendButton.offsetParent !== null) {
								// Use nextTick again to ensure iframe has loaded
								Alpine.nextTick(() => {
									sendButton.focus();
								});
							}
						}
					});
				}
			} else {
				// Close modal and show error
				Alpine.store('modals').close('email-preview-modal');
				this.showToast(data.data?.message || 'Failed to generate preview', 'error');
			}

		} catch (error) {
			console.error('Error generating preview:', error);
			Alpine.store('modals').close('email-preview-modal');
			this.showToast('An error occurred while generating the preview', 'error');
		} finally {
			this.previewingEmail = null;
		}
	},

	async previewEmailFromKebab(emailId) {
		// Get default values from the panel if it exists
		let defaultSubject = '';
		let defaultBody = '';

		const subjectInput = document.getElementById('affwp_settings[' + emailId + '_subject]');
		if (subjectInput) {
			defaultSubject = subjectInput.getAttribute('value') || '';
		}

		// Get the body field from the button's data attribute
		if (event && event.currentTarget) {
			const button = event.currentTarget;
			const bodyField = button.dataset.bodyField || (emailId + '_body');
			const bodyInput = document.getElementById('affwp_settings[' + bodyField + ']');
			if (bodyInput) {
				defaultBody = bodyInput.getAttribute('value') || '';
			}
		}

		// Call preview function directly
		await this.previewEmail(emailId, defaultSubject, defaultBody);
	},

	async sendTestEmailQuick(emailId, defaultSubject, defaultBody) {
		this.testingEmail = emailId;

		try {
			const formData = new FormData();
			formData.append('action', 'affwp_send_test_email');
			formData.append('email_id', emailId);
			formData.append('recipient', this.testRecipient);
			formData.append('subject', defaultSubject);
			formData.append('body', defaultBody);
			formData.append('nonce', affwpEmailConfig.nonces.testEmail);

			const response = await fetch(ajaxurl, {
				method: 'POST',
				body: formData
			});

			const data = await response.json();

			if (data.success) {
				this.showToast(data.data.message || 'Test email sent successfully!', 'success');
			} else {
				this.showToast(data.data?.message || 'Failed to send test email', 'error');
			}

		} catch (error) {
			console.error('Error sending test email:', error);
			this.showToast('An error occurred while sending the test email', 'error');
		} finally {
			this.testingEmail = null;
		}
	},

	showToast(message, type = 'success') {
		// Clear any existing timeout
		if (this.toastTimeout) {
			clearTimeout(this.toastTimeout);
		}

		// Hide any existing toast first
		this.toastVisible = false;

		// Set message and type, then show after a tick
		this.$nextTick(() => {
			this.toastMessage = message;
			this.toastType = type;

			// Use Alpine's nextTick to ensure DOM is updated
			this.$nextTick(() => {
				this.toastVisible = true;

				// Auto-hide after 5 seconds
				this.toastTimeout = setTimeout(() => {
					this.toastVisible = false;
					// Clean up message after animation completes
					setTimeout(() => {
						this.toastMessage = '';
					}, 300);
				}, 5000);
			});
		});
	},

	// Tag insertion methods
	initializeTagInsertion() {
		// Track focus on all subject fields
		document.addEventListener('focus', (e) => {
			if (e.target.matches && e.target.matches('input[id*="_subject"]')) {
				this.lastFocusedField = e.target;
				this.lastFocusedEditor = null;
				// Store cursor position
				this.cursorPositions[e.target.id] = {
					start: e.target.selectionStart,
					end: e.target.selectionEnd
				};
			}
		}, true);

		// Track selection changes in subject fields
		document.addEventListener('selectionchange', (e) => {
			if (this.lastFocusedField && document.activeElement === this.lastFocusedField) {
				this.cursorPositions[this.lastFocusedField.id] = {
					start: this.lastFocusedField.selectionStart,
					end: this.lastFocusedField.selectionEnd
				};
			}
		});

		// Track TinyMCE editor focus
		if (typeof tinymce !== 'undefined') {
			tinymce.on('AddEditor', (e) => {
				e.editor.on('focus', () => {
					this.lastFocusedEditor = e.editor;
					this.lastFocusedField = null;
				});
			});
		}
	},

	insertTag(tag, emailId) {
		// Determine which field to insert into
		if (this.lastFocusedField && this.lastFocusedField.id.includes(emailId)) {
			// Insert into subject field
			this.insertIntoTextField(this.lastFocusedField, tag);
		} else if (this.lastFocusedEditor) {
			// Insert into last focused TinyMCE editor
			this.insertIntoTinyMCE(this.lastFocusedEditor, tag);
		} else {
			// Try to find the TinyMCE editor for this email
			// Look for any TinyMCE editor that contains this email ID
			let editorFound = false;
			if (typeof tinymce !== 'undefined') {
				tinymce.editors.forEach((editor) => {
					if (editor.id.includes(emailId.replace(/-/g, '_'))) {
						this.insertIntoTinyMCE(editor, tag);
						editorFound = true;
					}
				});
			}

			if (!editorFound) {
				// Fallback: try to find the subject field for this email
				const subjectField = document.getElementById('affwp_settings[' + emailId + '_subject]');
				if (subjectField) {
					// Focus the field first
					subjectField.focus();
					this.insertIntoTextField(subjectField, tag);
				} else {
					// Last resort: find any textarea with this email ID
					const textareas = document.querySelectorAll('textarea[id*="' + emailId.replace(/-/g, '_') + '"]');
					if (textareas.length > 0) {
						this.insertIntoTextarea(textareas[0], tag);
					}
				}
			}
		}

		// Show brief feedback
		this.showToast('Tag ' + tag + ' inserted', 'success');
	},

	insertIntoTextField(field, tag) {
		const cursorData = this.cursorPositions[field.id] || { start: field.selectionStart, end: field.selectionEnd };
		const start = cursorData.start || 0;
		const end = cursorData.end || start;

		const value = field.value;
		const newValue = value.substring(0, start) + tag + value.substring(end);

		field.value = newValue;
		// Set cursor position after the inserted tag
		const newPosition = start + tag.length;
		field.setSelectionRange(newPosition, newPosition);
		field.focus();

		// Update stored cursor position
		this.cursorPositions[field.id] = { start: newPosition, end: newPosition };

		// Trigger input event for any listeners
		field.dispatchEvent(new Event('input', { bubbles: true }));
	},

	insertIntoTextarea(textarea, tag) {
		const start = textarea.selectionStart || 0;
		const end = textarea.selectionEnd || start;

		const value = textarea.value;
		const newValue = value.substring(0, start) + tag + value.substring(end);

		textarea.value = newValue;
		// Set cursor position after the inserted tag
		const newPosition = start + tag.length;
		textarea.setSelectionRange(newPosition, newPosition);
		textarea.focus();

		// Trigger input event
		textarea.dispatchEvent(new Event('input', { bubbles: true }));
	},

		insertIntoTinyMCE(editor, tag) {
			// Insert at current cursor position
			editor.execCommand('mceInsertContent', false, tag);
			editor.focus();
		}
	}));
});
</script>

<?php
// Render the webhook configuration modal
affwp_modal(
	[
		'id'                => 'webhook-configuration-modal',
		'title'             => __( 'Webhook Configuration Required', 'affiliate-wp' ),
		'variant'           => 'warning',
		'icon'              => [
			'name' => 'email-warning',
		],
		'content'           => affwp_emails_get_webhook_modal_content(),
		'show_close'        => true,
		'close_on_backdrop' => true,
		'close_on_escape'   => true,
		'footer_actions'    => [
			[
				'text'       => __( 'Cancel', 'affiliate-wp' ),
				'variant'    => 'secondary',
				'attributes' => [
					'@click' => "\$store.modals.close('webhook-configuration-modal')",
				],
			],
			[
				'text'       => __( 'Configure Webhooks', 'affiliate-wp' ),
				'variant'    => 'primary',
				'href'       => admin_url( 'admin.php?page=affiliate-wp-settings&tab=payouts#stripe-webhooks' ),
				'attributes' => [
					'autofocus' => true,
				],
			],
		],
	]
);

// Render the approval configuration modal
affwp_modal(
	[
		'id'             => 'approval-configuration-modal',
		'title'          => __( 'Affiliate Approval Required', 'affiliate-wp' ),
		'size'           => 'lg',
		'variant'        => 'info',
		'icon'           => [
			'name'    => 'information-circle',
			'variant' => 'info',
		],
		'content'        => '<p>' . __( 'This email notification requires the "Require Approval" setting to be enabled. When enabled, new affiliate applications will need to be manually approved before affiliates can start earning referrals.', 'affiliate-wp' ) . '</p>' .
								'<p class="mt-2">' . __( 'This allows you to review affiliate applications and send appropriate acceptance or rejection emails.', 'affiliate-wp' ) . '</p>',
		'footer_actions' => [
			[
				'text'    => __( 'Cancel', 'affiliate-wp' ),
				'variant' => 'secondary',
			],
			[
				'text'      => __( 'Enable Affiliate Approval', 'affiliate-wp' ),
				'variant'   => 'primary',
				'href'      => admin_url( 'admin.php?page=affiliate-wp-settings&tab=affiliates#require_approval' ),
				'autofocus' => true,
			],
		],
	]
);
?>

<div x-data="emailSettings">
	<!-- Main Content Container -->
	<div class="affwp-ui relative z-10 max-w-4xl py-8 space-y-4">
		<!-- General Email Settings -->
	<div class="bg-white rounded-lg border border-gray-200" x-cloak>
		<!-- Section Header (Collapsible) -->
		<div class="px-6 py-4 border-b border-gray-200 cursor-pointer flex items-center justify-between"
			@click="generalSettingsOpen = !generalSettingsOpen">
			<div>
				<h2 class="text-lg font-semibold text-gray-900"><?php esc_html_e( 'General Email Settings', 'affiliate-wp' ); ?></h2>
				<p class="text-sm text-gray-600 mt-1"><?php esc_html_e( 'Default email settings for your affiliate program', 'affiliate-wp' ); ?></p>
			</div>
			<svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
				:class="generalSettingsOpen ? '-rotate-90' : ''"
				fill="none" stroke="currentColor" viewBox="0 0 24 24">
				<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
			</svg>
		</div>

		<!-- Section Content -->
		<div class="overflow-hidden" x-show="generalSettingsOpen" x-collapse.duration.300ms>
			<div class="p-6">
				<div class="space-y-6">
			<!-- Logo Field -->
			<div>
				<label for="affwp_settings[email_logo]" class="block text-sm font-medium text-gray-700 mb-2">
					<?php esc_html_e( 'Email Logo', 'affiliate-wp' ); ?>
				</label>
				<div class="flex items-start space-x-4">
					<div class="flex-1">
						<input
							type="url"
							id="affwp_settings[email_logo]"
							name="affwp_settings[email_logo]"
							x-model="emailLogo"
							value="<?php echo esc_attr( $email_logo ); ?>"
							class="px-4 py-2 w-full text-base rounded-lg border border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-300"
							placeholder="<?php esc_attr_e( 'Enter URL or select from media library', 'affiliate-wp' ); ?>"
						/>
						<p class="mt-2 text-sm text-gray-600">
							<?php esc_html_e( 'Upload or choose a logo to be displayed at the top of emails.', 'affiliate-wp' ); ?>
						</p>
					</div>
					<?php
					affwp_button(
						[
							'text'       => __( 'Media Library', 'affiliate-wp' ),
							'variant'    => 'secondary',
							'type'       => 'button',
							'size'       => 'lg',
							'attributes' => [
								'@click' => 'openMediaLibrary()',
							],
						]
					);
					?>
				</div>
				<!-- Logo Preview -->
				<div x-show="emailLogo" x-cloak class="mt-4">
					<p class="text-sm font-medium text-gray-700 mb-2"><?php esc_html_e( 'Preview:', 'affiliate-wp' ); ?></p>
					<div class="border border-gray-200 rounded-lg p-4">
						<img :src="emailLogo" alt="<?php esc_attr_e( 'Email logo preview', 'affiliate-wp' ); ?>" class="max-h-32 object-contain">
					</div>
				</div>
			</div>

			<!-- Email Template -->
			<div class="pt-6 border-t border-gray-200">
				<label for="affwp_settings_email_template" class="block text-sm font-medium text-gray-700 mb-2">
					<?php esc_html_e( 'Email Template', 'affiliate-wp' ); ?>
				</label>
				<div class="grid grid-cols-1 affwp-ignore-select2">
					<select
						id="affwp_settings_email_template"
						name="affwp_settings[email_template]"
						class="affwp-ignore-select2 col-start-1 row-start-1 w-full appearance-none rounded-lg bg-white py-2 pr-10 pl-4 text-base text-gray-900 border border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-300"
					>
						<?php foreach ( $email_templates as $template_id => $template_name ) : ?>
							<option value="<?php echo esc_attr( $template_id ); ?>" <?php selected( $email_template, $template_id ); ?>>
								<?php echo esc_html( $template_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="pointer-events-none col-start-1 row-start-1 mr-3 size-5 self-center justify-self-end text-gray-500">
						<path d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
					</svg>
				</div>
				<p class="mt-2 text-sm text-gray-600">
					<?php esc_html_e( 'Choose a template to use for email notifications.', 'affiliate-wp' ); ?>
				</p>
			</div>

			<!-- From Name -->
			<div class="pt-6 border-t border-gray-200">
				<label for="affwp_settings[from_name]" class="block text-sm font-medium text-gray-700 mb-2">
					<?php esc_html_e( 'From Name', 'affiliate-wp' ); ?>
				</label>
				<input
					type="text"
					id="affwp_settings[from_name]"
					name="affwp_settings[from_name]"
					value="<?php echo esc_attr( $from_name ); ?>"
					class="px-4 py-2 w-full text-base rounded-lg border border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-300"
					placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"
				/>
				<p class="mt-2 text-sm text-gray-600">
					<?php esc_html_e( 'The name that emails come from. This is usually your site name.', 'affiliate-wp' ); ?>
				</p>
			</div>

			<!-- From Email -->
			<div class="pt-6 border-t border-gray-200">
				<label for="affwp_settings[from_email]" class="block text-sm font-medium text-gray-700 mb-2">
					<?php esc_html_e( 'From Email', 'affiliate-wp' ); ?>
				</label>
				<input
					type="email"
					id="affwp_settings[from_email]"
					name="affwp_settings[from_email]"
					value="<?php echo esc_attr( $from_email ); ?>"
					class="px-4 py-2 w-full text-base rounded-lg border border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-300"
					placeholder="<?php echo esc_attr( get_bloginfo( 'admin_email' ) ); ?>"
				/>
				<p class="mt-2 text-sm text-gray-600">
					<?php esc_html_e( 'The email address to send emails from. This will act as the "from" and "reply-to" address.', 'affiliate-wp' ); ?>
				</p>
			</div>

			<!-- Affiliate Manager Email -->
			<div class="pt-6 border-t border-gray-200">
				<label for="affwp_settings[affiliate_manager_email]" class="block text-sm font-medium text-gray-700 mb-2">
					<?php esc_html_e( 'Affiliate Manager Email', 'affiliate-wp' ); ?>
				</label>
				<input
					type="text"
					id="affwp_settings[affiliate_manager_email]"
					name="affwp_settings[affiliate_manager_email]"
					value="<?php echo esc_attr( $affiliate_manager_email ); ?>"
					class="px-4 py-2 w-full text-base rounded-lg border border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-300"
					placeholder="<?php echo esc_attr( get_bloginfo( 'admin_email' ) ); ?>"
				/>
				<p class="mt-2 text-sm text-gray-600">
					<?php esc_html_e( 'The email address(es) to receive affiliate manager notifications. Separate multiple email addresses with a comma (,). The admin email address will be used unless overridden.', 'affiliate-wp' ); ?>
				</p>
			</div>

			<!-- Affiliate Email Summaries -->
			<div class="pt-6 border-t border-gray-200">
				<div class="flex items-start justify-between">
					<div class="flex-1 pr-4">
						<label for="affwp_settings[affiliate_email_summaries]"
							class="block text-sm font-medium text-gray-700 mb-1 cursor-pointer"
							@click="emailSummariesEnabled = !emailSummariesEnabled">
							<?php esc_html_e( 'Affiliate Email Summaries', 'affiliate-wp' ); ?>
						</label>
						<p class="text-sm text-gray-600 mb-2">
							<?php esc_html_e( 'Send your affiliates a monthly email summary.', 'affiliate-wp' ); ?>
						</p>
						<div class="flex items-center space-x-3 text-sm">
							<?php
							affwp_link(
								[
									'text'     => __( 'Learn more in our documentation', 'affiliate-wp' ),
									'href'     => affwp_utm_link( 'https://affiliatewp.com/docs/affiliate-email-summaries', 'settings-emails', 'Affiliate Email Summaries' ),
									'external' => true,
								]
							);
							?>
							<span class="text-gray-400">•</span>
							<?php
							$preview_url = add_query_arg(
								[
									'affwp_notify_monthly_affiliate_email_summary' => '1',
									'preview'  => '1',
									'_wpnonce' => wp_create_nonce( 'preview_email_summary' ),
								],
								admin_url()
							);
							affwp_link(
								[
									'text'     => __( 'View Example', 'affiliate-wp' ),
									'href'     => $preview_url,
									'external' => true,
								]
							);
							?>
						</div>
						<?php if ( is_multisite() ) : ?>
							<p class="mt-2 text-sm text-amber-600">
								<?php esc_html_e( 'Note: Email summaries are not available on multisite installations.', 'affiliate-wp' ); ?>
							</p>
						<?php endif; ?>
					</div>
					<?php
					affwp_toggle(
						[
							'name'         => 'affwp_settings[affiliate_email_summaries]',
							'label'        => __( 'Enable email summaries', 'affiliate-wp' ),
							'checked'      => $affiliate_email_summaries,
							'size'         => 'md',
							'color'        => 'blue',
							'alpine_model' => 'emailSummariesEnabled',
							'disabled'     => is_multisite(),
							'attributes'   => [
								'@change' => 'emailSummariesEnabled = $event.target.checked',
							],
						]
					);
					?>
				</div>
			</div>
				</div>
			</div>
		</div>
	</div>


	<!-- Email Groups -->
	<?php foreach ( $email_groups as $group_id => $group ) : ?>
		<?php
		// Determine the Alpine variable name for this group.
		$group_var = '';
		if ( 'core' === $group_id ) {
			$group_var = 'affiliateWPEmailsOpen';
		} elseif ( 'stripe' === $group_id ) {
			$group_var = 'stripeEmailsOpen';
		} elseif ( 'custom' === $group_id ) {
			$group_var = 'customEmailsOpen';
		}
		?>

		<!-- Email Group Section -->
		<div class="bg-white rounded-lg border border-gray-200" x-cloak>
			<!-- Group Header -->
			<div class="px-6 py-4 bg-white2 border-b border-gray-200 cursor-pointer flex items-center justify-between"
				@click="<?php echo esc_attr( $group_var ); ?> = !<?php echo esc_attr( $group_var ); ?>">
				<div>
					<h2 class="text-lg font-semibold text-gray-900">
						<?php echo esc_html( $group['title'] ); ?>
					</h2>
					<p class="text-sm text-gray-600 mt-1">
						<?php echo esc_html( $group['description'] ); ?>
					</p>
				</div>
				<svg class="w-5 h-5 text-gray-400 transition-transform duration-200"
					:class="<?php echo esc_attr( $group_var ); ?> ? '-rotate-90' : ''"
					fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
				</svg>
			</div>

			<!-- Group Content -->
			<div class="overflow-visible" x-show="<?php echo esc_attr( $group_var ); ?>" x-collapse.duration.300ms>
				<div class="p-6">
				<?php if ( 'stripe' === $group_id && ! $stripe_enabled ) : ?>

					<div class="rounded-md bg-blue-50 p-4 mb-6">
						<div class="flex">
							<div class="shrink-0">
								<svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5 text-blue-400">
								<path d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" fill-rule="evenodd" />
								</svg>
							</div>
							<div class="ml-3 flex-1">
								<p class="text-sm text-blue-700"><?php esc_html_e( 'The emails below require Stripe Payouts. Enable it to automate payment notifications for both managers and affiliates.', 'affiliate-wp' ); ?></p>
								<div class="mt-3">


									<?php
									affwp_button(
										[
											'href'    => admin_url( 'admin.php?page=affiliate-wp-settings&tab=payouts#stripe' ),
											'text'    => __( 'Set up Stripe Payouts', 'affiliate-wp' ),
											'variant' => 'secondary',
											'size'    => 'sm',
											'icon'    => [
												'position' => 'after',
												'svg'      => '<svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>',
											],
										]
									);
									?>
								</div>
							</div>
						</div>
					</div>

				<?php endif; ?>

				<!-- Subgroups and Email Lists -->
				<?php if ( isset( $group['subgroups'] ) && ! empty( $group['subgroups'] ) ) : ?>
					<?php
					$subgroup_count = 0;
					foreach ( $group['subgroups'] as $subgroup_id => $subgroup ) :
						++$subgroup_count;
						?>
						<?php if ( ! empty( $subgroup['emails'] ) ) : ?>
							<?php if ( count( $group['subgroups'] ) > 1 ) : ?>
								<!-- Subgroup Header (Outside Border) -->
								<div class="<?php echo $subgroup_count > 1 ? 'mt-6' : ''; ?> mb-3">
									<h3 class="mb-2 text-lg font-semibold text-gray-900">
										<?php echo esc_html( $subgroup['title'] ); ?>
									</h3>
								</div>
							<?php endif; ?>

							<!-- Email List (Separated Cards) -->
							<div class="space-y-3">
								<!-- Individual Email Items -->
								<?php foreach ( $subgroup['emails'] as $email_id => $email ) : ?>
									<?php $panel_var = 'emailPanel_' . str_replace( '-', '_', $email_id ); ?>
									<!-- Email Item (Individual Card) -->
									<div class="bg-white rounded-lg border transition-all duration-200 relative"
										:class="<?php echo esc_attr( $panel_var ); ?> ? 'border-gray-300 border-l-4 border-l-[var(--color-affwp-brand-500)]' : 'border-gray-200'">
										<div class="px-6 py-4 flex items-center justify-between transition-colors cursor-pointer rounded-lg"
											:class="<?php echo esc_attr( $panel_var ); ?> ? 'bg-gray-50' : 'hover:bg-gray-50'"
											@click="toggleAccordion('<?php echo esc_attr( $email_id ); ?>', $event)">
											<div class="flex items-center text-left flex-1">
												<div class="flex-1">
							<div class="flex items-center gap-2 mb-1">
								<h3 id="email-<?php echo esc_attr( $email_id ); ?>-heading" class="text-base font-medium text-gray-900">
									<?php echo esc_html( $email['name'] ); ?>
								</h3>

									<?php if ( $email['webhook_required'] && $stripe_enabled && ! $webhooks_configured ) : ?>
										<span @click.stop="$store.modals.open('webhook-configuration-modal')" class="cursor-help">
											<?php
											affwp_badge(
												[
													'text'    => __( 'Webhooks Required', 'affiliate-wp' ),
													'variant' => 'warning',
													'size'    => 'xs',
												]
											);
											?>
										</span>
									<?php endif; ?>

									<?php if ( ! empty( $email['approval_required'] ) && ! $approval_required ) : ?>
									<span @click.stop="$store.modals.open('approval-configuration-modal')" class="cursor-help">
										<?php
										affwp_badge(
											[
												'text'    => __( 'Approval Required', 'affiliate-wp' ),
												'variant' => 'info',
												'size'    => 'xs',
											]
										);
										?>
									</span>
								<?php endif; ?>
							</div>
							<p class="text-sm text-gray-500">
									<?php echo esc_html( $email['description'] ); ?>
							</p>
												</div>
											</div>
											<div class="flex items-center space-x-2">
												<!-- Configure Button -->
												<?php
												affwp_configure_button(
													[
														'type'        => 'button',
														'panel_var'   => $panel_var,
														'panel_id'    => 'email-' . $email_id . '-panel',
														'method_name' => $email['name'],
													]
												);
												?>

												<!-- Kebab Menu for Quick Actions -->
												<div class="relative" x-data="{ kebabOpen: false }">
													<button
														type="button"
														@click.stop="kebabOpen = !kebabOpen"
														@click.away="kebabOpen = false"
														class="cursor-pointer p-2 text-gray-400 hover:text-gray-600 rounded-md transition-colors"
														aria-label="<?php esc_attr_e( 'More actions', 'affiliate-wp' ); ?>"
													>
														<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
															<path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
														</svg>
													</button>

													<!-- Dropdown Menu -->
													<div
														x-show="kebabOpen"
														x-cloak
														x-transition:enter="transition ease-out duration-100"
														x-transition:enter-start="transform opacity-0 scale-95"
														x-transition:enter-end="transform opacity-100 scale-100"
														x-transition:leave="transition ease-in duration-75"
														x-transition:leave-start="transform opacity-100 scale-100"
														x-transition:leave-end="transform opacity-0 scale-95"
														class="absolute right-0 z-50 mt-2 w-48 origin-top-right rounded-md bg-white shadow-lg border border-gray-200 focus:outline-none"
													>
														<div class="py-1">
															<button
																type="button"
																@click.stop="kebabOpen = false; $nextTick(() => { previewEmailFromKebab('<?php echo esc_attr( $email_id ); ?>'); })"
																data-body-field="<?php echo esc_attr( isset( $email['body_field'] ) ? $email['body_field'] : $email_id . '_body' ); ?>"
																class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 flex items-center cursor-pointer"
															>
																<svg class="mr-3 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																	<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
																	<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
																</svg>
																<?php esc_html_e( 'Preview Email', 'affiliate-wp' ); ?>
															</button>

															<button
																type="button"
																@click.stop="kebabOpen = false; sendTestEmailQuick('<?php echo esc_attr( $email_id ); ?>', '<?php echo esc_js( $email['subject'] ); ?>', '<?php echo esc_js( $email['body'] ); ?>')"
																data-body-field="<?php echo esc_attr( isset( $email['body_field'] ) ? $email['body_field'] : $email_id . '_body' ); ?>"
																class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 flex items-center cursor-pointer"
															>
																<svg class="mr-3 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
																	<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
																</svg>
																<?php esc_html_e( 'Send Test Email', 'affiliate-wp' ); ?>
															</button>
														</div>
													</div>
												</div>

												<!-- Toggle Switch -->
												<?php
												$toggle_var = 'toggle_' . str_replace( '-', '_', $email_id );

												// Map email IDs to their database keys for core emails.
												$email_db_keys = [
													'registration'       => 'admin_affiliate_registration_email',
													'new_admin_referral' => 'admin_new_referral_email',
													'referral'           => 'affiliate_new_referral_email',
													'accepted'           => 'affiliate_application_accepted_email',
													'pending'            => 'affiliate_application_pending_email',
													'rejection'          => 'affiliate_application_rejected_email',
												];

												// Use mapped key if exists, otherwise use email_id as-is (for Stripe)
												$db_key       = isset( $email_db_keys[ $email_id ] ) ? $email_db_keys[ $email_id ] : $email_id;
												$setting_name = sprintf( 'affwp_settings[email_notifications][%s]', $db_key );

												// Check if webhooks are required but not configured (only when Stripe is enabled).
												$webhook_required = $email['webhook_required'] && ! $webhooks_configured && $stripe_enabled;

												// Check if approval is required but not configured.
												$approval_needed = ! empty( $email['approval_required'] ) && ! $approval_required;

												$toggle_attributes = [
													'@click.stop' => '',  // Prevent accordion toggle.
												];

												if ( $webhook_required ) {
													// Intercept change event to show modal instead of actually toggling..
													$toggle_attributes['@change'] = 'handleWebhookToggle(\'' . esc_attr( $email_id ) . '\', true, $event)';
												} elseif ( $approval_needed ) {
													// Intercept change event to show approval modal instead of actually toggling..
													$toggle_attributes['@change'] = 'handleApprovalToggle(\'' . esc_attr( $email_id ) . '\', $event)';
												} else {
													// Normal change behavior..
													$toggle_attributes['@change'] = $toggle_var . ' = $event.target.checked';
												}

												affwp_toggle(
													[
														'name' => $setting_name,
														/* translators: %s: Email name */
														'label' => sprintf( __( 'Enable %s', 'affiliate-wp' ), $email['name'] ),
														'checked' => $email['enabled'],
														'size' => 'md',
														'color' => 'blue',
														'alpine_model' => $toggle_var,
														'disabled' => false,  // Never disable so click events work.
														'attributes' => $toggle_attributes,
													]
												);
												?>
											</div>
									</div>

									<!-- Email Configuration Panel -->
									<div
										id="email-<?php echo esc_attr( $email_id ); ?>-panel"
										role="region"
										aria-labelledby="email-<?php echo esc_attr( $email_id ); ?>-heading"
										x-show="<?php echo esc_attr( $panel_var ); ?>"
										x-collapse.duration.300ms
										x-cloak
										@keydown.escape="<?php echo esc_attr( $panel_var ); ?> = false"
										tabindex="-1"
										class="border-t border-gray-200 focus:outline-none"
									>
										<div class="p-6 space-y-6">
						<!-- Subject Field -->
						<div>
							<label for="affwp_settings[<?php echo esc_attr( $email_id ); ?>_subject]" class="block text-sm font-medium text-gray-700 mb-2">
									<?php esc_html_e( 'Email Subject', 'affiliate-wp' ); ?>
							</label>
							<input
								type="text"
								id="affwp_settings[<?php echo esc_attr( $email_id ); ?>_subject]"
								name="affwp_settings[<?php echo esc_attr( $email_id ); ?>_subject]"
								value="<?php echo esc_attr( $email['subject'] ); ?>"
								class="px-4 py-2 w-full text-base rounded-lg border border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-300"
							/>
						</div>

						<!-- Email Body -->
						<div>
							<label for="affwp_settings_<?php echo esc_attr( $email_id ); ?>_body" class="block text-sm font-medium text-gray-700 -mb-[24px]">
									<?php esc_html_e( 'Email Body', 'affiliate-wp' ); ?>
							</label>
							<div class="preserve-styles">
									<?php
									$body_field = isset( $email['body_field'] ) ? $email['body_field'] : $email_id . '_body';
									wp_editor(
										stripslashes( $email['body'] ),
										'affwp_settings_' . $body_field,
										[
											'textarea_name' => 'affwp_settings[' . $body_field . ']',
											'textarea_rows' => 15,
											'media_buttons' => false,
											'teeny'     => false,
											'quicktags' => true,
											'tinymce'   => [
												'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink,forecolor,undo,redo',
												'toolbar2' => '',
												'height'   => 500,
											],
										]
									);
									?>
							</div>
						</div>

						<!-- Available Template Tags Section -->
						<div class="mt-6">
							<div class="mb-2">
								<span class="text-sm font-medium text-gray-700">
									<?php esc_html_e( 'Template Tags', 'affiliate-wp' ); ?>
									<span class="text-xs text-gray-500 ml-1 font-normal"><?php esc_html_e( '(use in subject and body)', 'affiliate-wp' ); ?></span>
								</span>
							</div>
							<div class="flex flex-wrap gap-1.5">
									<?php
									// Get merge tag descriptions
									$email_instance   = new Affiliate_WP_Emails();
									$all_tags         = $email_instance->get_tags();
									$tag_descriptions = [];
									foreach ( $all_tags as $tag_info ) {
										// Store without curly braces as key since tags come with them
										$tag_descriptions[ $tag_info['tag'] ] = $tag_info['description'];
									}

									// Map tag variations to their core AffiliateWP equivalents
									// Some emails use 'username' while core uses 'user_name'
									if ( isset( $tag_descriptions['user_name'] ) ) {
										$tag_descriptions['username'] = $tag_descriptions['user_name'];
									}

									// Add Stripe Payouts specific tag descriptions and map common tags
									// Note: affiliate_name in Stripe Payouts is 'name' in core AffiliateWP
									if ( isset( $tag_descriptions['name'] ) ) {
										$tag_descriptions['affiliate_name'] = $tag_descriptions['name'];
									}
									if ( isset( $tag_descriptions['user_email'] ) ) {
										$tag_descriptions['affiliate_email'] = $tag_descriptions['user_email'];
									}

									// Map any other common variations
									if ( isset( $tag_descriptions['referral_url'] ) ) {
										$tag_descriptions['affiliate_url'] = $tag_descriptions['referral_url'];
									}

									// Add Stripe-specific tags that don't exist in core
									$stripe_tags = [
										'transfer_id' => __( 'The Stripe transfer ID', 'affiliate-wp' ),
									];

									// Merge with existing tags
									$tag_descriptions = array_merge( $tag_descriptions, $stripe_tags );

									// Parse and display individual tags
									$tags = explode( ', ', $email['tags'] );
									foreach ( $tags as $tag ) :
										$tag = trim( $tag );
										// Remove curly braces to look up description
										$tag_key     = str_replace( [ '{', '}' ], '', $tag );
										$description = isset( $tag_descriptions[ $tag_key ] )
											? $tag_descriptions[ $tag_key ]
											: __( 'Click to insert this tag', 'affiliate-wp' );

										// Build the tooltip HTML using affwp_tooltip function
										$tooltip_html = affwp_tooltip(
											[
												'content' => $description,
												'type'    => 'info',
											]
										);
										?>
										<span
											class="inline-block bg-gray-50 px-2 py-1 rounded text-xs text-gray-700 border border-gray-200 cursor-pointer hover:border-gray-300 transition-all duration-150 select-none"
											data-tooltip-html="<?php echo esc_attr( $tooltip_html ); ?>"
											@click="insertTag('<?php echo esc_js( $tag ); ?>', '<?php echo esc_js( $email_id ); ?>')"
											title="<?php esc_attr_e( 'Click to insert this tag', 'affiliate-wp' ); ?>"
										>
											<?php echo esc_html( $tag ); ?>
										</span>
									<?php endforeach; ?>
							</div>
						</div>

						<!-- Preview & Test Section -->
						<div class="mt-8">
							<h4 class="text-sm font-medium text-gray-700 mb-3">
									<?php esc_html_e( 'Preview & Test', 'affiliate-wp' ); ?>
							</h4>
							<div class="flex items-start justify-between gap-8">
								<!-- Preview Email Section (Left) -->
								<div>
									<?php
									affwp_button(
										[
											'text'       => __( 'Preview Email', 'affiliate-wp' ),
											'variant'    => 'secondary',
											'type'       => 'button',
											'size'       => 'md',
											'attributes' => [
												'@click' => 'previewEmail(\'' . esc_attr( $email_id ) . '\', \'' . esc_js( $email['subject'] ) . '\', \'' . esc_js( $email['body'] ) . '\')',
												':disabled' => 'previewingEmail === \'' . esc_attr( $email_id ) . '\'',
												'x-text' => 'previewingEmail === \'' . esc_attr( $email_id ) . '\' ? \'' . esc_js( __( 'Loading...', 'affiliate-wp' ) ) . '\' : \'' . esc_js( __( 'Preview Email', 'affiliate-wp' ) ) . '\'',
												'data-body-field' => esc_attr( $body_field ),
											],
										]
									);
									?>
								</div>

								<!-- Send Test Email Section (Right) -->
								<div class="flex-1 max-w-md">
									<div class="flex items-center space-x-3">
										<input
											type="email"
											x-model="testRecipient"
											placeholder="<?php esc_attr_e( 'Test email recipient', 'affiliate-wp' ); ?>"
											class="flex-1 px-4 py-2 text-base rounded-lg border border-gray-300 transition-colors focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-300"
										/>
										<?php
										// Send test button.
										affwp_button(
											[
												'text'    => __( 'Send Test Email', 'affiliate-wp' ),
												'variant' => 'secondary',
												'type'    => 'button',
												'size'    => 'md',
												'attributes' => [
													'@click' => 'sendTestEmail(\'' . esc_attr( $email_id ) . '\', $event)',
													':disabled' => 'testingEmail === \'' . esc_attr( $email_id ) . '\'',
													'data-default-subject' => esc_attr( $email['subject'] ),
													'data-default-body' => esc_attr( $email['body'] ),
													'data-body-field' => esc_attr( $body_field ),
													'x-text' => 'testingEmail === \'' . esc_attr( $email_id ) . '\' ? \'' . esc_js( __( 'Sending...', 'affiliate-wp' ) ) . '\' : \'' . esc_js( __( 'Send Test Email', 'affiliate-wp' ) ) . '\'',
												],
											]
										);
										?>
									</div>
								</div>
							</div>
						</div>
										</div>
									</div>
								</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					<?php endforeach; ?>
				<?php endif; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
	</div><!-- End Main Content Container -->

	<?php
	// Render the email preview modal.
	affwp_modal(
		[
			'id'             => 'email-preview-modal',
			'title'          => __( 'Email Preview', 'affiliate-wp' ),
			'size'           => 'xl',
			'variant'        => 'default',
			'icon'           => [
				'name'    => 'eye',
				'variant' => 'default',
			],
			'attributes'     => [
				'x-data' => '{
					get modalData() {
						const modal = $store.modals.registry["email-preview-modal"];
						return modal?.data || {};
					},
					get isLoading() {
						return this.modalData.isLoading || false;
					},
					get currentEmailType() {
						return this.modalData.emailType || "";
					}
				}',
			],
			'custom_header'  => '
				<div class="py-4 border-b border-gray-200">
					<div class="flex items-center justify-between">
						<h3 class="text-lg font-semibold text-gray-900">' . __( 'Email Preview', 'affiliate-wp' ) . '</h3>
						<button @click="$store.modals.close(&quot;email-preview-modal&quot;)" class="text-gray-400 hover:text-gray-500 cursor-pointer">
							<span class="sr-only">' . __( 'Close', 'affiliate-wp' ) . '</span>
							<svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
								<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
							</svg>
						</button>
					</div>
					<div class="mt-2 text-sm text-gray-600" x-text="modalData.dataSource || &apos;&apos;">' . '</div>
				</div>
			',
			'content'        => '
				<div class="space-y-4">
					<!-- Email Header Info -->
					<div class="bg-gray-50 p-4 rounded-lg space-y-2">
						<div class="flex">
							<span class="font-medium text-gray-700 w-20">' . __( 'From:', 'affiliate-wp' ) . '</span>
							<template x-if="isLoading">
								<span class="flex-1 bg-gray-200 animate-pulse h-5 rounded"></span>
							</template>
							<template x-if="!isLoading">
								<span class="text-gray-900 flex-1" x-text="modalData.from || &apos;&apos;"></span>
							</template>
						</div>
						<div class="flex">
							<span class="font-medium text-gray-700 w-20">' . __( 'Subject:', 'affiliate-wp' ) . '</span>
							<template x-if="isLoading">
								<span class="flex-1 bg-gray-200 animate-pulse h-5 rounded"></span>
							</template>
							<template x-if="!isLoading">
								<span class="text-gray-900 flex-1" x-text="modalData.subject || &apos;&apos;"></span>
							</template>
						</div>
					</div>


					<!-- Email Body Preview -->
					<div class="border border-gray-200 rounded-lg overflow-hidden">
						<!-- Loading State -->
						<template x-if="isLoading">
							<div class="bg-white p-8">
								<div class="flex flex-col items-center justify-center space-y-4">
									<svg class="animate-spin h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24">
										<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
										<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
									</svg>
									<p class="text-sm text-gray-600">' . __( 'Loading email preview...', 'affiliate-wp' ) . '</p>
								</div>
							</div>
						</template>

						<!-- Email Content -->
						<template x-if="!isLoading">
							<div class="bg-white">
								<iframe
									:srcdoc="modalData.bodyHtml || &apos;&apos;"
									class="w-full"
									style="min-height: 500px; max-height: 600px;"
									frameborder="0"
									@load="$el.style.height = Math.min($el.contentDocument.body.scrollHeight + 20, 600) + &apos;px&apos;"
								></iframe>
							</div>
						</template>
					</div>
				</div>
			',
			'footer_actions' => [
				[
					'text'       => __( 'Send Test Email', 'affiliate-wp' ),
					'variant'    => 'primary',
					'attributes' => [
						'x-show' => '!isLoading',
						'@click' => 'window.dispatchEvent(new CustomEvent(\'send-test-email\', { detail: { emailType: currentEmailType }})); $store.modals.close(\'email-preview-modal\')',
					],
				],
			],
		]
	);
	?>


	<!-- Global notification live region -->
	<div aria-live="assertive" class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-end sm:p-6 z-50">
		<div class="flex w-full flex-col items-center space-y-4 sm:items-end">
			<!-- Toast Notification -->
			<!-- Screen reader announcement for toast messages -->
			<div aria-live="polite" aria-atomic="true" class="sr-only" x-text="toastMessage"></div>

			<div x-show="toastVisible" x-cloak
				x-transition:enter="transform transition-all duration-300 ease-out"
				x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-4"
				x-transition:enter-end="translate-y-0 translate-x-0 opacity-100"
				x-transition:leave="transform transition-all duration-200 ease-in"
				x-transition:leave-start="translate-y-0 translate-x-0 opacity-100"
				x-transition:leave-end="translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-4"
				class="affwp-ui pointer-events-auto transition-all duration-300 ease-out w-full max-w-sm transform rounded-lg bg-white shadow-lg outline-1 outline-black/5">
				<div class="p-4">
					<div class="flex items-start">
						<div class="shrink-0">
							<!-- Success Icon -->
							<svg x-show="toastType === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 text-green-400">
								<path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<!-- Error Icon -->
							<svg x-show="toastType === 'error'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 text-red-400">
								<path d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
							<!-- Info Icon -->
							<svg x-show="toastType === 'info'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 text-blue-400">
								<path d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" stroke-linecap="round" stroke-linejoin="round" />
							</svg>
						</div>
						<div class="ml-3 w-0 flex-1 pt-0.5">
							<p class="text-sm font-medium text-gray-900" x-text="toastMessage"></p>
						</div>
						<div class="ml-4 flex shrink-0">
							<button type="button" @click="toastVisible = false; if(toastTimeout) clearTimeout(toastTimeout); setTimeout(() => toastMessage = '', 300)" class="inline-flex rounded-md text-gray-400 hover:text-gray-500 focus:outline-2 focus:outline-offset-2 focus:outline-blue-600">
								<span class="sr-only">Close</span>
								<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5">
									<path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
								</svg>
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

</div><!-- End Alpine.js wrapper -->
