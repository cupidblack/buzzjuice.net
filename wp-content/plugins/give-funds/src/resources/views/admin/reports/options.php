<?php defined( 'ABSPATH' ) or exit; ?>
<tr class="give-export-donations-fund-fields">
	<td scope="row" class="row-title">
		<label><?php esc_html_e( 'Fund Columns:', 'give-funds' ); ?></label>
	</td>
	<td class="give-field-wrap">
		<div class="give-clearfix">
			<ul class="give-export-option-ul give-export-option-fields">
				<li>
					<label for="give-export-fund-id">
						<input
							type="checkbox"
							checked="checked"
							name="give_give_donations_export_option[fund_id]"
							id="give-export-fund-id" />
						<?php esc_html_e( 'Fund ID', 'give-funds' ); ?>
					</label>
				</li>
				<li>
					<label for="give-export-fund-title">
						<input
							type="checkbox"
							checked="checked"
							name="give_give_donations_export_option[fund_title]"
							id="give-export-fund-title" />
						<?php esc_html_e( 'Fund Title', 'give-funds' ); ?>
					</label>
				</li>
				<li>
					<label for="give-export-fund-description">
						<input
							type="checkbox"
							name="give_give_donations_export_option[fund_description]"
							id="give-export-fund-description" />
						<?php esc_html_e( 'Fund Description', 'give-funds' ); ?>
					</label>
				</li>
			</ul>
		</div>
	</td>
</tr>
