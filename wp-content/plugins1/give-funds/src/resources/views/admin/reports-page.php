<?php defined( 'ABSPATH' ) or exit;
// Reports page markup
// #reports-app is replaced by React app
?>

<div id="give-funds-reports">
	<div class="wrap give-settings-page" style="position: relative">
		<div class="give-settings-header">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Reports', 'give-funds' ); ?> <span class="give-settings-heading-sep dashicons dashicons-arrow-right-alt2"></span> <?php esc_html_e( 'Funds', 'give-funds' ); ?>
			</h1>
		</div>
		<div class="nav-tab-wrapper give-nav-tab-wrapper">
			<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-report' ); ?>" class="nav-tab">
				<?php esc_html_e( 'Overview', 'give-funds' ); ?>
			</a>
			<a href="<?php echo $reportsUrl; ?>" class="nav-tab nav-tab-active">
				<?php esc_html_e( 'Funds', 'give-funds' ); ?>
			</a>
			<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-reports&legacy=true' ); ?>" class="nav-tab">
				<?php esc_html_e( 'Legacy Reports', 'give-funds' ); ?>
			</a>
		</div>
	</div>
</div>
