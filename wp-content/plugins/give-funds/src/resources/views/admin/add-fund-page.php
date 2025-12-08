<?php defined( 'ABSPATH' ) or exit; ?>

<div class="wrap give-settings-page">

	<div class="give-settings-header">
		<h1 class="wp-heading-inline">
			<?php esc_html_e( 'Add Fund', 'give-funds' ); ?>
			<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-funds' ); ?>" class="page-title-action">
				<?php esc_html_e( 'Go back', 'give-funds' ); ?>
			</a>
		</h1>
	</div>

	<form method="post">

		<input type="hidden" name="give-funds-default" value="0" id="give-funds-default" />
		<?php wp_nonce_field( 'add-fund', 'give-funds-nonce' ); ?>

		<div id="give-funds-management-wrapper">
			<div class="row">
				<div class="column">
					<div class="give-funds-form-row">
						<label for="give-funds-title">
							<?php esc_html_e( 'Title', 'give-funds' ); ?>
						</label>
						<?php $title = isset( $_POST['give-funds-title'] ) ? give_clean( $_POST['give-funds-title'] ) : ''; ?>
						<input
							type="text"
							id="give-funds-title"
							name="give-funds-title"
							value="<?php echo $title; ?>"
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
						<?php $description = isset( $_POST['give-funds-description'] ) ? give_clean( $_POST['give-funds-description'] ) : ''; ?>
						<input
							type="text"
							id="give-funds-description"
							name="give-funds-description"
							value="<?php echo $description; ?>"
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
					name="give-funds-add-fund"
					class="button button-primary"
					value="<?php esc_html_e( 'Add fund', 'give-funds' ); ?>"
				/>
			</div>

		</div>
	</form>
</div>

