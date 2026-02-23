<?php defined( 'ABSPATH' ) or exit; ?>
<div class="give-admin-box-inside">
	<p>
		<label for="give-payment-status" class="strong"><?php esc_html_e( 'Fund', 'give-funds' ); ?>:</label>&nbsp;
		<select id="give-funds-select" class="medium-text" name="give-selected-fund">
			<?php foreach ( $funds as $fund ) : ?>
				<?php $selected = ( $fund->getId() === $selectedFund ) ? 'selected' : ''; ?>
				<option value="<?php echo $fund->getId(); ?>" <?php echo $selected; ?>>
					<?php echo $fund->getTitle(); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
</div>
