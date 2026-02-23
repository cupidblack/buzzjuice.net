<?php defined( 'ABSPATH' ) or exit; ?>
<div class="give-funds-select-fund-wrap">
	<p>
		<label for="give-funds-select-fund">
			<?php echo $label; ?>
		</label>
	</p>
	<p>
		<select id="give-funds-select-fund" class="give-select">
			<?php foreach ( $funds as $fund ) : ?>
				<option value="<?php echo $fund->getId(); ?>">
					<?php echo $fund->getTitle(); ?>
					<?php if ( $fund->isDefault() ) : ?>
						( <?php esc_html_e( 'Default fund', 'give-funds' ); ?> )
					<?php endif; ?>
				</option>
			<?php endforeach; ?>
		</select>
	</p>
</div>
