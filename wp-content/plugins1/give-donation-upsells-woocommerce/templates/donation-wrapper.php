<tr>
	<td colspan="6" class="give_wc_donation_section_td">
		<div class="give_wc_donation_section">
			<table>
				<?php
				// Get the give_wc_intro_text mode.
				$intro_text = WC_Admin_Settings::get_option( 'give_wc_intro_text' );

				if ( ! empty( $intro_text ) ) {
					?>
					<tr>
						<td class="give-wc-intro-row">
							<span class="give_wc_intro_text">
								<?php echo esc_html( $intro_text ); ?>
							</span>
						</td>
					</tr>
					<?php
				}

				// Get the Give Donation Forms.
				$give_donation_forms = WC_Admin_Settings::get_option( 'give_wc_donation_forms' );

				// Render the donation form.
				Give_WooCommerce_Frontend::render_donation_forms( $give_donation_forms );
				?>
			</table>
		</div>
		<?php
		// Show button to add/remove Give donation items.
		if ( is_checkout() ) {
			echo sprintf(
				'<div class="give-wc-update-donations"><button type="button" class="button alt" name="give_wc_update_donation" id="give_wc_update_donation" value="%1$s" data-value="%1$s">%1$s</button></div>',
				__( 'Update Donation', 'give-woocommerce' )
			);
		}
		?>
	</td>
</tr>
