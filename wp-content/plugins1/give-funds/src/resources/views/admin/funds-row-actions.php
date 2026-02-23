<?php defined( 'ABSPATH' ) or exit; ?>

<?php echo $fund_name; ?>
<?php if ( $is_default ) : ?>
	( <?php esc_html_e( 'Default fund', 'give-funds' ); ?> )
<?php endif; ?>

<div class="row-actions">
	<span class="view">
		<a href="<?php echo $overview_link; ?>" aria-label="<?php esc_html_e( 'Fund overview', 'give-funds' ); ?>">
			<?php esc_html_e( 'Overview', 'give-funds' ); ?>
		</a> |
	</span>
	<span class="edit">
		<a href="<?php echo $edit_link; ?>" aria-label="<?php esc_html_e( 'Edit Fund', 'give-funds' ); ?>">
			<?php esc_html_e( 'Edit', 'give-funds' ); ?>
		</a>
	</span>
	<?php if ( ! $is_default ) : ?>
		|  <span class="delete give-funds-delete-fund">
			<a
				href="<?php echo $delete_link; ?>"
				data-id="<?php echo $fund_id; ?>"
				data-name="<?php echo $fund_name; ?>"
				aria-label="<?php printf( esc_html__( 'Delete fund %s', 'give-funds' ), $fund_name ); ?>"
			>
				<?php esc_html_e( 'Delete', 'give-funds' ); ?>
			</a>
		</span>
	<?php endif ?>
</div>
