<?php defined( 'ABSPATH' ) or exit;
// Reports page markup
// #reports-app is replaced by React app
?>
<input type="hidden" id="give-funds-report-fund-id" value="<?php echo $fund->getId(); ?>" />
<input type="hidden" id="give-funds-report-fund-title" value="<?php echo $fund->getTitle(); ?>" />

<div id="give-funds-overview">
	<div class="wrap give-settings-page" style="position: relative">
		<div class="give-settings-header">
			<h1 class="wp-heading-inline">
				<?php esc_html_e( 'Edit Fund', 'give-funds' ); ?>
			</h1>
		</div>
		<div class="nav-tab-wrapper give-nav-tab-wrapper">
			<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-fund-overview&id=' . $fund->getId() ); ?>" class="nav-tab nav-tab-active">
				<?php esc_html_e( 'Overview', 'give-funds' ); ?>
			</a>
			<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-edit-fund&id=' . $fund->getId() ); ?>" class="nav-tab">
				<?php esc_html_e( 'Edit Fund', 'give-funds' ); ?>
			</a>
		</div>
	</div>
</div>
