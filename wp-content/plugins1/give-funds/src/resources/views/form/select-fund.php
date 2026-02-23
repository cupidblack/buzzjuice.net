<?php defined( 'ABSPATH' ) or exit; ?>
<div class="form-row form-row-wide">
	<div class="give-select-fund-row">
		<label for="give-funds-select">
			<?php echo esc_html( $label ); ?>
		</label>
		<select class="give-funds-select give-select" name="give-selected-fund">
			<?php foreach ( $funds as $fund ) : ?>
				<option value="<?php echo $fund->getId(); ?>" data-description="<?php echo esc_attr( $fund->getDescription() ); ?>">
					<?php echo esc_html( $fund->getTitle() ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<div class="give-funds-fund-description">
			<?php
			if ( ! empty( $funds[0]->getDescription() ) ) {
				echo esc_html( $funds[0]->getDescription() );
			}
			?>
		</div>
	</div>
</div>
