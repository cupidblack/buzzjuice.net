<tr>
	<td id="give-wc-form-<?php echo absint( $form_id ); ?>" class="give-wc-give-form-row <?php echo in_array( $form_id, $form_ids ) ? 'give-wc-active-row' : ''; ?>"
		data-display_type="<?php echo esc_attr( $display_type ) ?>" <?php echo $form_args; ?> >
		<div class="give-wc-give-form-head">
			<label class="give-wc-give-form-label give-wc-checkbox-row">
				<input type="checkbox" class="give-wc-form-selector" name="give_wc_form-data[<?php echo $form_id; ?>][selected]" data-give_form_id="<?php echo absint( $form_id ); ?>" <?php checked( in_array( $form_id, $form_ids ), true, true ); ?>/>
				<span class="give-wc-form-title"><?php echo esc_html( give_wc_render_donation_form_title( $form_id ) ); ?></span>
			</label>
		</div>
		<div class="give-wc-donation-inside <?php echo ! in_array( $form_id, $form_ids ) ? 'give-wc-hidden' : ''; ?>" data-form_id="<?php echo absint( $form_id ); ?>">
			<?php if ( ! empty( $form_description ) ) : ?>
				<p class="give_wc_donation_desc"><?php echo esc_html( $form_description ); ?></p>
			<?php endif; ?>
			<div class="give-wc-donation-amount-wrapper">

				<?php
				/**
				 * Output variable price container if this donation form has them enabled.
				 */
				if ( give_has_variable_prices( $form_id ) ) : ?>
					<div class="give-wc-inside-left-panel">
						<?php give_wc_render_multi_level( $form_id, $wc_donation_session ); ?>
					</div>
				<?php endif; ?>

				<div class="give-wc-donation-amount-wrap<?php echo ( ! give_has_variable_prices( $form_id ) ) ? ' give-wc-donation-amount-wrap-no-multilevel' : '' ;?>">
						<span class="give-wc-donation-amount-text">
							<?php _e( 'Donation Amount:', 'give-woocommerce' ); ?>
						</span>
					<span class="give-wc-fixed-donation-amount">
						<?php
						/**
						 * If custom amount enabled then output accordingly.
						 */
						if ( $form_custom_amount ) {
							if ( 'before' === $currency_position ) { ?>
								<span class="give_wc_currency_symbol give_wc_price_before"><?php echo give_currency_symbol( give_get_currency() ); ?></span>
							<?php }
						} else { ?>
							<span class="give-wc-form-amount">
								<?php echo give_currency_filter( give_format_amount( $default_amount ), array( 'position' => $currency_position ) ); ?>
							</span>
						<?php } ?>
						<span class="give-tooltip hint--top hint--medium hint--bounce give_wc_hide_tooltip" rel="tooltip"
							  aria-label="<?php _e( 'The minimum custom donation amount for this form is', 'give-woocommerce' ); ?>">
							<input
									class="give-text-input give-amount-top give-wc-amount-field"
									id="give-amount"
									name="give_wc_form-data[<?php echo absint( $form_id ); ?>][give-amount]"
									autocomplete="off"
									data-auto_populated_amount="<?php echo give_format_amount( $default_amount ); ?>"
									type="<?php echo $form_custom_amount ? 'text' : 'hidden'; ?>"
									value="<?php echo give_format_amount( $default_amount ); ?>"
							/>
						</span>
						<?php
						if ( $form_custom_amount && 'after' === $currency_position ) {
							?>
							<span class="give_wc_currency_symbol give_wc_price_after">
									<?php echo give_currency_symbol( give_get_currency() ); ?>
							</span>
							<?php
						}
						?>
						</span>
					<input class="give-text-input give-amount-top give-wc-amount-field" id="give-amount" name="give_wc_session_id" type="hidden" value="<?php echo esc_attr(
						$give_wc_session_id ); ?>"/>
				</div>
			</div>
		</div>
	</td>
</tr>
