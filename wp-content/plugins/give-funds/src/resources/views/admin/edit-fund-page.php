<?php defined( 'ABSPATH' ) or exit; ?>

<div class="wrap give-settings-page">

	<div class="give-settings-header">
		<h1 class="wp-heading-inline">
			<?php esc_html_e( 'Edit Fund', 'give-funds' ); ?>
		</h1>
	</div>

	<div class="nav-tab-wrapper give-nav-tab-wrapper">
		<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-fund-overview&id=' . $fund->getId() ); ?>" class="nav-tab">
			<?php esc_html_e( 'Overview', 'give-funds' ); ?>
		</a>
		<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-edit-fund&id=' . $fund->getId() ); ?>" class="nav-tab nav-tab-active">
			<?php esc_html_e( 'Edit Fund', 'give-funds' ); ?>
		</a>
	</div>

	<form method="post">

		<input type="hidden" name="give-funds-id" value="<?php echo $fund->getId(); ?>" />
		<input type="hidden" name="give-funds-default" value="<?php echo $fund->isDefault() ? 1 : 0; ?>" id="give-funds-default" />
		<?php wp_nonce_field( 'edit-fund-' . $fund->getId(), 'give-funds-nonce' ); ?>

		<div id="give-funds-management-wrapper">
			<div class="row">
				<div class="column">
					<div class="give-funds-form-row">
						<label for="give-funds-title">
							<?php esc_html_e( 'Title', 'give-funds' ); ?>
						</label>
						<input
							type="text"
							id="give-funds-title"
							name="give-funds-title"
							value="<?php echo $fund->getTitle(); ?>"
							required
						/>
						<p class="give-funds-info">
							<?php esc_html_e( 'The name of the fund displayed when making a designation.', 'give-funds' ); ?>
						</p>
					</div>

					<div class="give-funds-form-row">
						<label for="give-funds-description">
							<?php esc_html_e( 'Description', 'give-funds' ); ?>
						</label>
						<input
							type="text"
							id="give-funds-description"
							name="give-funds-description"
							value="<?php echo $fund->getDescription(); ?>"
						/>
						<p class="give-funds-info">
							<?php esc_html_e( 'Additional context displayed if the fund is currently designated.', 'give-funds' ); ?>
						</p>
					</div>
				</div>
			</div>

			<div class="give-submit-wrap">
				<input
					type="submit"
					class="button button-primary"
					name="give-funds-edit-fund"
					value="<?php esc_html_e( 'Update', 'give-funds' ); ?>"
				/>
				<?php if ( ! $fund->isDefault() ) : ?>
					<a
						href="<?php echo $deleteLink; ?>"
						class="button give-funds-delete-btn"
						onclick="return confirm('<?php esc_html_e( 'Delete fund', 'give-funds' ); ?>');"
					>
						<?php esc_html_e( 'Delete', 'give-funds' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</form>
</div>
