<?php defined( 'ABSPATH' ) or exit; ?>

<div class="wrap give-funds-list-table">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Funds', 'give-funds' ); ?>
		<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-add-fund' ); ?>" class="page-title-action">
			<?php esc_html_e( 'New Fund', 'give-funds' ); ?>
		</a>
	</h1>
	<hr class="wp-header-end">
	<form id="give-funds-list-table-form" method="post">
		<?php wp_nonce_field( 'funds-bulk-action', 'give-funds-bulk-nonce' ); ?>
		<input type="hidden" name="give-funds-selected-fund" value="" />
		<?php echo $table->display(); ?>
	</form>
</div>
